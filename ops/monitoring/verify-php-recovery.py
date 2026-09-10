#!/usr/bin/env python3
"""Opt-in root canary: transient test services only, never production PHP.

Run explicitly with sudo; not part of the offline unit-test suite. Reads the
candidate drop-in and verifies it with systemd, then exercises its exact four
properties on uniquely named disposable services. No production units change.
"""
import configparser
import os
from pathlib import Path
import subprocess
import tempfile
import time
import uuid


def command(*args, check=True):
    return subprocess.run(args, check=check, text=True, capture_output=True, timeout=10)


def state(unit):
    result = command("systemctl", "show", unit, "-p", "ActiveState", "-p", "SubState",
                     "-p", "NRestarts", "-p", "Result")
    return dict(line.split("=", 1) for line in result.stdout.splitlines())


def wait_for(unit, predicate, deadline):
    until = time.monotonic() + deadline
    while time.monotonic() < until:
        current = state(unit)
        if predicate(current):
            return current
        time.sleep(0.25)
    raise RuntimeError(f"canary timeout: {unit} {state(unit)}")


def main():
    if os.geteuid() != 0:
        raise SystemExit("Run explicitly as root; this creates only transient canary units.")
    source = Path(__file__).with_name("php-fpm-recovery.conf").read_text()
    policy = configparser.ConfigParser()
    policy.optionxform = str
    policy.read_string(source)
    properties = [f"{key}={value}" for section in policy.sections() for key, value in policy[section].items()]
    if set(properties) != {"Restart=on-failure", "RestartSec=5s", "StartLimitIntervalSec=300", "StartLimitBurst=5"}:
        raise RuntimeError("Unexpected policy; review and adapt the canary before running")
    flags = [arg for prop in properties for arg in ("--property", prop)]
    prefix = "manacost-php-recovery-canary-" + uuid.uuid4().hex[:12]
    units = []
    with tempfile.TemporaryDirectory(prefix="manacost-php-recovery-canary-") as temporary:
        directory = Path(temporary)
        unit_file = directory / (prefix + ".service")
        unit_file.write_text("[Service]\nType=simple\nExecStart=/usr/bin/true\n" + source)
        command("systemd-analyze", "verify", str(unit_file))
        print("PASS systemd-analyze verify", flush=True)

        def start(suffix, *args, remain=False):
            unit = prefix + "-" + suffix + ".service"
            units.append(unit)
            command("systemd-run", "--quiet", "--unit", unit, "--service-type=simple", *flags,
                    "--property", f"RemainAfterExit={'yes' if remain else 'no'}", "--", *args)
            return unit

        try:
            once = start("once", "/bin/bash", "-c",
                         'if [[ -e "$1" ]]; then exit 0; fi; touch -- "$1"; exit 42',
                         "canary", str(directory / "failed-once"), remain=True)
            recovered = wait_for(once, lambda value: value["SubState"] == "exited", 12)
            assert recovered["NRestarts"] == "1" and recovered["Result"] == "success", recovered
            print("PASS abnormal exit recovered once; clean exit did not restart", flush=True)

            stopped = start("manual-stop", "/usr/bin/sleep", "60")
            wait_for(stopped, lambda value: value["SubState"] == "running", 5)
            command("systemctl", "stop", stopped)
            time.sleep(6)
            assert state(stopped)["ActiveState"] == "inactive", state(stopped)
            print("PASS manual stop stayed stopped beyond RestartSec", flush=True)

            attempts = directory / "burst-attempts"
            burst = start("burst", "/bin/bash", "-c", 'printf "attempt\\n" >> "$1"; exit 1',
                          "canary", str(attempts))
            # systemd 252 retains Result=exit-code when the restart limit hits.
            # Prove actual behavior, not a version-specific Result string.
            limited = wait_for(burst, lambda value: value["ActiveState"] == "failed", 35)
            assert int(limited["NRestarts"]) == 5, limited
            time.sleep(6)
            assert state(burst)["ActiveState"] == "failed", state(burst)
            assert attempts.read_text().splitlines() == ["attempt"] * 5
            print("PASS five actual starts then stable failed state; restart limiter works", flush=True)
        finally:
            for unit in units:
                command("systemctl", "stop", unit, check=False)
                command("systemctl", "reset-failed", unit, check=False)
            print("Canary units stopped/reset; production units untouched", flush=True)


if __name__ == "__main__":
    main()
