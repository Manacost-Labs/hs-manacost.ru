"""Opt-in OAuth/MCP snippets must parse without changing the running server."""
import pathlib
import shutil
import subprocess
import tempfile
import unittest

ROOT = pathlib.Path(__file__).resolve().parents[2]


class McpOAuthProxyTests(unittest.TestCase):
    def test_isolated_nginx_syntax_and_auth_boundaries(self):
        nginx = shutil.which('nginx') or '/usr/sbin/nginx'
        self.assertTrue(pathlib.Path(nginx).is_file(), 'nginx is a required project test dependency')
        for environment in ('staging', 'production'):
            with self.subTest(environment=environment), tempfile.TemporaryDirectory(prefix='mcp-nginx-') as directory:
                origin = (ROOT / f'ops/articles-mcp/origin-{environment}.conf').read_text()
                edge = (ROOT / f'ops/articles-mcp/proxy-{environment}.conf').read_text()
                self.assertIn('proxy_set_header Authorization "";', origin)
                self.assertIn('proxy_set_header Authorization $http_authorization;', origin)
                self.assertIn('proxy_set_header Cookie "";', origin)
                self.assertIn('proxy_set_header Authorization $http_authorization;', edge)
                self.assertIn('proxy_ssl_verify on;', edge)
                self.assertIn('access_log off;', edge)
                self.assertNotIn('hs-manacost.com', origin + edge)
                self.assertEqual('auth_basic off;' in origin, environment == 'staging')
                self.assertEqual('auth_basic off;' in edge, environment == 'staging')
                edge = edge.replace('/etc/nginx/ssl/hs-manacost-reader-origin-ca.pem', '/etc/ssl/certs/ca-certificates.crt')
                conf = pathlib.Path(directory) / 'nginx.conf'
                conf.write_text(f'''pid {directory}/nginx.pid;
error_log {directory}/error.log;
events {{ worker_connections 16; }}
http {{ access_log off;
upstream hs_manacost_reader_origin {{ server 127.0.0.1:18443; }}
upstream hs_manacost_reader_production_origin {{ server 127.0.0.1:18443; }}
server {{ listen 127.0.0.1:19081; {origin} }}
server {{ listen 127.0.0.1:19082; {edge} }}
}}''')
                result = subprocess.run([nginx, '-t', '-p', directory, '-c', str(conf)], capture_output=True, text=True)
                self.assertEqual(result.returncode, 0, result.stderr)


if __name__ == '__main__':
    unittest.main()
