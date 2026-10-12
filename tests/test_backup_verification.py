from __future__ import annotations

import json
import os
import subprocess
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

MOCK = '''#!/usr/bin/env python3
import datetime,gzip,json,os,sys
from pathlib import Path
a=sys.argv[1:];tool=Path(sys.argv[0]).name
with open(os.environ['CALLS'],'a') as f:f.write(json.dumps([tool,*a])+'\\n')
if tool=='rclone':
 if a[0]=='check':sys.exit(int(os.environ.get('CHECK_EXIT','0')))
 if a[0]=='lsf':
  if '--dirs-only' in a:print('20000101T000000Z/')
  elif not os.environ.get('EMPTY_SOURCE'):print('sample.png\\t10')
 if a[0]=='copyto' and ':' in a[1] and ':' not in a[2]:
  corrupted=os.environ.get('CORRUPT_BACKUP') and 'backup-bucket' in a[1]
  Path(a[2]).write_bytes(b'bad' if corrupted else b'original')
elif tool=='restic':
 now=datetime.datetime.now(datetime.timezone.utc)
 if 'snapshots' in a:print(json.dumps([{'id':'older','time':(now-datetime.timedelta(hours=24)).isoformat()},{'id':'newest','time':now.isoformat()}]))
 if 'ls' in a:print(json.dumps({'struct_type':'node','type':'file','path':'/backup/wordpress.sql.gz'}))
 if 'restore' in a:
  p=Path(a[a.index('--target')+1])/'backup/wordpress.sql.gz';p.parent.mkdir(parents=True);p.write_bytes(gzip.compress(b'-- synthetic dump'))
elif tool=='docker':
 if 'exec' in a and '-i' in a:sys.stdin.buffer.read()
 if any('COUNT(*)' in x for x in a):print('1')
'''


class BackupVerificationTest(unittest.TestCase):
    def setUp(self) -> None:
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.directory = Path(self.temp.name)
        for tool in ['rclone', 'restic', 'docker']:
            p = self.directory / tool
            p.write_text(MOCK)
            p.chmod(0o755)
        self.calls_file = self.directory / 'calls.jsonl'
        self.env = {
            **os.environ,
            'PATH': str(self.directory) + ':' + os.environ['PATH'],
            'CALLS': str(self.calls_file),
            'HS_S3_SOURCE_REMOTE': 'source:source-bucket/uploads',
            'HS_S3_BACKUP_REMOTE': 'backup:backup-bucket/site',
            'HS_RESTIC_REPOSITORY': '/synthetic/restic',
            'HS_RESTIC_PASSWORD_FILE': '/synthetic/password',
            'HS_DB_BACKUP_GLOB': '*/wordpress.sql.gz',
            'HS_DB_RESTORE_DATABASE': 'expected_wp',
            'HS_S3_RESTORE_SAMPLE': 'sample.png',
        }

    def run_script(self, name: str, **env: str) -> subprocess.CompletedProcess:
        return subprocess.run(['bash', str(ROOT / 'ops/backup' / name)], env={**self.env, **env}, capture_output=True, text=True, timeout=15)

    def calls(self) -> list[list[str]]:
        return [json.loads(line) for line in self.calls_file.read_text().splitlines()] if self.calls_file.exists() else []

    def test_snapshot_verifies_before_publishing_and_preserves_old_versions(self) -> None:
        result = self.run_script('s3-snapshot.sh')
        self.assertEqual(result.returncode, 0, result.stderr)
        calls = self.calls()
        operations = [call[1] for call in calls]
        self.assertIn('check', operations)
        self.assertLess(operations.index('sync'), operations.index('check'))
        self.assertLess(operations.index('check'), operations.index('copyto'))
        self.assertNotIn('purge', operations)
        self.assertIn('--backup-dir', next(c for c in calls if c[1] == 'sync'))

    def test_dry_run_performs_no_copy_or_success_publication(self) -> None:
        result = self.run_script('s3-snapshot.sh', HS_S3_DRY_RUN='1')
        self.assertEqual(result.returncode, 0, result.stderr)
        sync = next(c for c in self.calls() if c[1] == 'sync')
        self.assertIn('--dry-run', sync)
        self.assertFalse(any(c[1] in ['copyto', 'purge'] for c in self.calls()))
        self.assertNotIn('S3 snapshot verified', result.stdout)

    def test_failed_verification_cannot_publish_success_manifest(self) -> None:
        result = self.run_script('s3-snapshot.sh', CHECK_EXIT='1')
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn('S3 snapshot verified', result.stdout)
        self.assertFalse(any(c[1] == 'copyto' for c in self.calls()))

    def test_same_bucket_under_two_remote_aliases_is_rejected_before_io(self) -> None:
        result = self.run_script('s3-snapshot.sh', HS_S3_SOURCE_REMOTE='one:shared-bucket/uploads', HS_S3_BACKUP_REMOTE='two:shared-bucket/backup')
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.calls(), [])

    def test_empty_source_cannot_move_the_entire_existing_backup_to_versions(self) -> None:
        result = self.run_script('s3-snapshot.sh', EMPTY_SOURCE='1')
        self.assertNotEqual(result.returncode, 0)
        self.assertFalse(any(c[1] == 'sync' for c in self.calls()))

    def test_missing_credential_field_fails_before_any_storage_request(self) -> None:
        credentials = self.directory / 'storage.json'
        credentials.write_text(json.dumps({'endpoint': 'https://example.invalid', 'access_key': 'synthetic'}))
        credentials.chmod(0o600)
        result = self.run_script('s3-snapshot.sh', HS_S3_CREDENTIALS_FILE=str(credentials))
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.calls(), [])
        self.assertNotIn('synthetic', result.stdout + result.stderr)

    def test_installer_selects_only_backup_and_restore_units(self) -> None:
        import re
        source = (ROOT / 'ops/backup/install-timers.sh').read_text()
        fragment = re.search(r'for unit in .*?\ndone', source, re.S)
        self.assertIsNotNone(fragment)
        units = self.directory / 'ops/systemd'
        units.mkdir(parents=True)
        expected = {f'hs-manacost-{name}.{suffix}' for name in ['s3-backup', 'restore-drill'] for suffix in ['service', 'timer']}
        for filename in expected | {'hs-manacost-unrelated.service'}:
            (units / filename).write_text('synthetic unit')
        destination = self.directory / 'installed'
        destination.mkdir()
        script = 'ROOT_DIR=' + str(self.directory) + '\n' + fragment.group(0).replace('/etc/systemd/system/', str(destination) + '/')
        result = subprocess.run(['bash', '-ec', script], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual({p.name for p in destination.iterdir()}, expected)

    def test_restore_uses_latest_snapshot_actual_database_and_private_password_file(self) -> None:
        result = self.run_script('restore-drill.sh')
        self.assertEqual(result.returncode, 0, result.stderr)
        calls = self.calls()
        restore = next(c for c in calls if c[:2] == ['restic', 'restore'])
        self.assertEqual(restore[2], 'newest')
        queries = [c for c in calls if c[0] == 'docker' and any('COUNT(*)' in x for x in c)]
        self.assertEqual(len(queries), 3)
        self.assertTrue(all('expected_wp' in c for c in queries))
        run = next(c for c in calls if c[:2] == ['docker', 'run'])
        self.assertIn('--network', run)
        self.assertIn('none', run)
        self.assertIn('--env-file', run)
        self.assertIn('--memory=12g', run)
        self.assertFalse(any('MARIADB_ROOT_PASSWORD=' in x for x in run))
        copies = [c for c in calls if c[:2] == ['rclone', 'copyto']]
        self.assertTrue(any(c[2].startswith(self.env['HS_S3_SOURCE_REMOTE']) for c in copies))

    def test_restore_fails_when_backup_sample_differs_from_source(self) -> None:
        result = self.run_script('restore-drill.sh', CORRUPT_BACKUP='1')
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn('Restore drill passed', result.stdout)


if __name__ == '__main__':
    unittest.main()
