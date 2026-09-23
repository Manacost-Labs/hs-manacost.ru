#!/usr/bin/env python3
"""Explicit diagnostics for this worktree's disposable WordPress stack only."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import uuid

WPSCAN = 'wpscanteam/wpscan@sha256:07d1f5a2e64e575fe4ce1b7d9d4f8d2df33471c0254323b600fb61f770d8924e'
PLAYWRIGHT = 'mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--query-monitor', action='store_true')
    parser.add_argument('--wpscan', action='store_true')
    parser.add_argument('--advisories', action='store_true')
    args = parser.parse_args()
    if not (args.query_monitor or args.wpscan or args.advisories):
        parser.error('Choose an explicit diagnostic')
    root = Path(__file__).resolve().parents[2]
    os.chdir(root)
    os.umask(0o077)
    envfile = root / '.artifacts/integration/runtime.env'
    values = dict(line.split('=', 1) for line in envfile.read_text().splitlines() if line.startswith('WP_TEST_') and '=' in line)
    port = int(values['WP_TEST_PORT'])
    if not 1024 <= port <= 65535:
        raise ValueError('Invalid integration port')
    # Same ownership derivation as scope.sh; never target a shared/global stack.
    project = 'hs-manacost-integration-' + hashlib.sha256(str(root).encode()).hexdigest()[:12]
    compose = ['docker', 'compose', '--project-name', project, '--env-file', str(envfile), '-f', 'ops/integration/compose.yml']
    container = subprocess.check_output([*compose, 'ps', '-q', 'wordpress'], text=True).strip()
    if not container:
        raise ValueError('Start this worktree\'s disposable stack first')
    published = subprocess.check_output([*compose, 'port', 'wordpress', '80'], text=True).strip()
    if published != f'127.0.0.1:{port}':
        raise ValueError('Integration port does not belong to this stack')
    reports = root / '.artifacts/quality-diagnostics'
    reports.mkdir(parents=True, exist_ok=True)
    cli = [*compose, 'run', '--rm', '--user', f'{os.getuid()}:{os.getgid()}', 'cli', '--allow-root']
    with (reports / 'tools.log').open('ab') as log:
        if args.query_monitor:
            active = subprocess.run([*cli, 'plugin', 'is-active', 'query-monitor'], stdout=log, stderr=log, check=False).returncode == 0
            browser_name = 'quality-query-monitor-' + uuid.uuid4().hex[:12]
            try:
                subprocess.run([*cli, 'plugin', 'install', 'query-monitor', '--version=4.0.7', '--activate'], stdout=log, stderr=log, timeout=180, check=True)
                subprocess.run(['docker', 'run', '--rm', '--name', browser_name, '--network', 'host', '--ipc=host', '--cpus=1', '--memory=1g',
                                '--pids-limit=256', '--user', f'{os.getuid()}:{os.getgid()}', '--env-file', str(envfile),
                                '-v', f'{root}:/work', '-w', '/work', PLAYWRIGHT, 'node', 'ops/integration/query-monitor.mjs',
                                '/work/.artifacts/quality-diagnostics/query-monitor.json'], stdout=log, stderr=log, timeout=90, check=True)
            finally:
                subprocess.run(['docker', 'stop', '--time', '3', browser_name], stdout=log, stderr=log, timeout=10, check=False)
                if not active:
                    subprocess.run([*cli, 'plugin', 'deactivate', 'query-monitor'], stdout=log, stderr=log, timeout=60, check=True)
        if args.advisories:
            from advisories import check_inventory
            core = subprocess.check_output([*cli, 'core', 'version'], stderr=log, text=True, timeout=60).strip()
            inventory = [{'kind': 'core', 'slug': core, 'version': core}]
            for kind in ('plugin', 'theme'):
                rows = json.loads(subprocess.check_output([*cli, kind, 'list', '--format=json'], stderr=log, text=True, timeout=60))
                inventory.extend({'kind': kind, 'slug': p['name'], 'version': p.get('version', '')}
                                 for p in rows if p.get('status') not in ('must-use', 'dropin'))
            report = check_inventory(inventory, reports / 'public-advisories')
            print(json.dumps({k: v for k, v in report.items() if k != 'components'}))
            if report['status'] != 'passed':
                return 2
        if args.wpscan:
            plugins = json.loads(subprocess.check_output([*cli, 'plugin', 'list', '--format=json'], stderr=log, text=True))
            names = [p['name'] for p in plugins if p.get('status') in ('active', 'inactive')]
            if not names or len(names) > 50 or any(not re.fullmatch(r'[a-zA-Z0-9_-]+', x) for x in names):
                raise ValueError('Expected a bounded installed-plugin inventory')
            (reports / 'plugins.txt').write_text('\n'.join(names) + '\n')
            env = dict(os.environ)
            if env.get('WPSCAN_API_TOKEN_FILE'):
                env['WPSCAN_API_TOKEN'] = Path(env['WPSCAN_API_TOKEN_FILE']).read_text().strip()
            scan_name = 'quality-wpscan-' + uuid.uuid4().hex[:12]
            try:
                result = subprocess.run(['docker', 'run', '--rm', '--name', scan_name, '--network', 'host', '--cpus=1', '--memory=512m',
                                     '--pids-limit=128', '--user', f'{os.getuid()}:{os.getgid()}', '--env', 'WPSCAN_API_TOKEN',
                                     '-v', f'{reports}:/reports', WPSCAN, '--url', f'http://127.0.0.1:{port}',
                                     '--detection-mode', 'passive', '--plugins-detection', 'passive', '--plugins-list', '/reports/plugins.txt',
                                     '--no-update', '--max-threads', '1', '--throttle', '200', '--request-timeout', '5', '--connect-timeout', '5',
                                     '--format', 'json', '--output', '/reports/wpscan.json'], env=env, stdout=log, stderr=log, timeout=90, check=False)
            except subprocess.TimeoutExpired:
                print(json.dumps({'status': 'failed', 'reason': 'WPScan exceeded 90 seconds'}))
                return 2
            finally:
                subprocess.run(['docker', 'stop', '--time', '3', scan_name], stdout=log, stderr=log, timeout=10, check=False)
            report = json.loads((reports / 'wpscan.json').read_text())
            complete = result.returncode == 0 and not report.get('scan_aborted') and not report.get('vuln_api', {}).get('error')
            print(json.dumps({'wpscan_exit': result.returncode, 'vulnerability_database_checked': complete,
                              'plugins': len(report.get('plugins', {})), 'report': str(reports / 'wpscan.json')}))
            if not complete:
                return 2
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
