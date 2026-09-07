#!/usr/bin/env python3
"""Exercise the versioned mirror header policy in an isolated local nginx.

Requires nginx >= 1.29.3 (production is 1.30.1). No production requests,
configuration, certificates, database or privileged ports are used.
"""
import http.client
import re
import shutil
import socket
import subprocess
import tempfile
import time
from pathlib import Path


ROOT = Path(__file__).resolve().parents[3]


def main():
    nginx = shutil.which('nginx') or '/usr/sbin/nginx'
    version = subprocess.run([nginx, '-v'], capture_output=True, text=True, check=True)
    parsed = re.search(r'nginx/(\d+)\.(\d+)\.(\d+)', version.stderr)
    assert parsed and tuple(map(int, parsed.groups())) >= (1, 29, 3), 'nginx >= 1.29.3 required'
    source = (ROOT / 'ops/nginx/mirror.conf').read_text()
    blocks = re.findall(r'# BEGIN MIRROR RESPONSE POLICY\n(.*?)# END MIRROR RESPONSE POLICY', source, re.S)
    assert len(blocks) == 2 and blocks[0] == blocks[1], 'both mirror server policies must match'
    with socket.socket() as candidate:
        candidate.bind(('127.0.0.1', 0))
        port = candidate.getsockname()[1]
    with tempfile.TemporaryDirectory(prefix='manacost-nginx-header-test-') as temp:
        config = Path(temp) / 'nginx.conf'
        servers = []
        for host, policy in (
            ('hs-manacost.ru', ''),
            ('hs-manacost.com', blocks[0]),
            ('test.hs-manacost.ru', 'add_header X-Robots-Tag "noindex, nofollow, noarchive" always;'),
        ):
            servers.append(f'''server {{
                listen 127.0.0.1:{port}; server_name {host};
                {policy}
                location / {{ return 200 'cached html without PHP headers'; }}
                location /missing {{ return 404; }}
                location /redirect {{ return 302 /; }}
                location /nested {{
                    add_header Cache-Control 'no-store' always;
                    return 403;
                }}
            }}''')
        config.write_text(f'''daemon off; master_process off;
            error_log {temp}/error.log; pid {temp}/nginx.pid;
            events {{ worker_connections 32; }}
            http {{ access_log off; {''.join(servers)} }}''')
        subprocess.run([nginx, '-t', '-c', str(config), '-p', temp], check=True)
        process = subprocess.Popen([nginx, '-c', str(config), '-p', temp])
        try:
            for attempt in range(50):
                try:
                    with socket.create_connection(('127.0.0.1', port), timeout=0.1):
                        break
                except OSError:
                    if process.poll() is not None or attempt == 49:
                        raise RuntimeError('isolated nginx did not start')
                    time.sleep(0.05)
            for host in ('hs-manacost.ru', 'hs-manacost.com', 'test.hs-manacost.ru'):
                paths = (('/', 200), ('/missing', 404), ('/redirect', 302))
                if host != 'test.hs-manacost.ru':
                    paths += (('/nested', 403),)
                for path, status in paths:
                    for _ in range(2):
                        client = http.client.HTTPConnection('127.0.0.1', port, timeout=3)
                        try:
                            client.request('GET', path, headers={'Host': host})
                            response = client.getresponse()
                            response.read()
                            assert response.status == status, (host, path, response.status)
                            robots = response.getheader('X-Robots-Tag', '')
                            marker = response.getheader('X-Manacost-Mirror', '')
                            if host == 'hs-manacost.com':
                                assert robots == 'noindex, follow' and marker == 'active', (host, path, robots, marker)
                            elif host == 'hs-manacost.ru':
                                assert robots == '' and marker == '', (host, path, robots, marker)
                            else:
                                assert robots == 'noindex, nofollow, noarchive' and marker == ''
                        finally:
                            client.close()
            print('Isolated nginx: 22 cold/warm header assertions passed')
        finally:
            process.terminate()
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=5)


if __name__ == '__main__':
    main()
