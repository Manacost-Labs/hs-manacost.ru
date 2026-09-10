#!/usr/bin/env python3
"""Inject operation failures in private paths with fake nginx/systemctl only."""
import os
import shutil
import subprocess
import tempfile
from pathlib import Path

SOURCE = Path(__file__).resolve().parent


def main():
    with tempfile.TemporaryDirectory(prefix="hs-media-deploy-test-") as temporary:
        root = Path(temporary)
        artifact_root = root / "etc/nginx/hs-media-negotiation"
        http = root / "etc/nginx/conf.d/hs-media-negotiation-canary.conf"
        server = root / "etc/nginx/vhosts-resources/hs-manacost.ru/00-media-negotiation-canary.conf"
        for directory in (http.parent, server.parent, root / "var/backups/hs-manacost-deploy", root / "bin"):
            directory.mkdir(parents=True)
        script = (SOURCE / "deploy-canary.sh").read_text()
        # Only path and EUID transport guards differ; the transaction is exact.
        script = script.replace('/etc/nginx/', f'{root}/etc/nginx/')
        script = script.replace('/var/backups/', f'{root}/var/backups/')
        script = "\n".join(line for line in script.splitlines() if not line.startswith('[[ $EUID'))
        copied_source = root / "source"
        copied_source.mkdir()
        (copied_source / "deploy.sh").write_text(script)
        for name in ("headers.conf", "http.conf", "proxy.conf", "server.conf", "install-http.inc", "install-server.inc"):
            shutil.copyfile(SOURCE / name, copied_source / name)
        for command in ("install", "mv", "nginx", "systemctl"):
            real = shutil.which(command) if command in ("install", "mv") else "/bin/true"
            wrapper = root / "bin" / command
            wrapper.write_text(f'''#!/bin/bash
set -eu
command_name={command}
if [[ ${{FAIL_COMMAND:-}} == "$command_name" && ! -e "$FAIL_MARKER" ]]; then
  matches=1
  if [[ -n ${{FAIL_TARGET:-}} ]]; then
    matches=0
    for argument in "$@"; do [[ $argument != "$FAIL_TARGET" ]] || matches=1; done
  fi
  if [[ $matches == 1 ]]; then touch "$FAIL_MARKER"; exit 71; fi
fi
exec {real} "$@"
''')
            wrapper.chmod(0o755)
        environment = dict(os.environ, PATH=f"{root}/bin:{os.environ['PATH']}")

        def run(action, backup=None, failure=None):
            current = dict(environment)
            marker = root / "failed-once"
            if marker.exists():
                marker.unlink()
            if failure:
                current.update(FAIL_COMMAND=failure[0], FAIL_TARGET=failure[1], FAIL_MARKER=str(marker))
            args = ["bash", str(copied_source / "deploy.sh"), action]
            if backup:
                args.append(backup)
            result = subprocess.run(args, env=current, capture_output=True, text=True)
            assert result.returncode == (71 if failure else 0), (action, failure, result.stdout, result.stderr)
            if failure:
                assert marker.exists(), "failure was not injected"
                assert "previous include state restored and reloaded" in result.stderr, result.stderr
            return result

        result = run("prepare")
        backup = result.stdout.split("PREPARED_INACTIVE ")[1].strip()
        assert artifact_root.exists() and not http.exists() and not server.exists()
        for failure in (("install", str(server)), ("nginx", ""), ("systemctl", "reload")):
            run("enable", backup, failure)
            assert not http.exists() and not server.exists(), failure
        run("enable", backup)
        for failure in (("mv", str(http)), ("nginx", ""), ("systemctl", "reload")):
            run("disable", backup, failure)
            assert http.read_bytes() == (SOURCE / "install-http.inc").read_bytes(), failure
            assert server.read_bytes() == (SOURCE / "install-server.inc").read_bytes(), failure
        run("disable", backup)
        assert not http.exists() and not server.exists()
        run("enable", backup)
        print("Canary deployment: enable/disable plus six injected failure recoveries PASS")


if __name__ == "__main__":
    main()
