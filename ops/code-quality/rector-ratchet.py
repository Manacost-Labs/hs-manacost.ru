#!/usr/bin/env python3
"""Fail new modernization debt; preserve the six reviewed legacy proposals."""
import hashlib
import json
from pathlib import Path
import subprocess
import sys


def compare(root, proposals, baseline):
    known = {item['path']: item for item in baseline['entries']}
    failures = []
    for item in proposals:
        path = item['file']
        evidence = {'path': path, 'source_sha256': hashlib.sha256((root / path).read_bytes()).hexdigest(),
                    'diff_sha256': hashlib.sha256(item['diff'].encode()).hexdigest()}
        if known.get(path) != evidence:
            failures.append(path)
    return failures


def main():
    root = Path(__file__).resolve().parents[2]
    result = subprocess.run([str(root / 'vendor/bin/rector'), 'process', '--config', 'config/rector-quality.php',
                             '--dry-run', '--no-progress-bar', '--output-format=json'], cwd=root,
                            capture_output=True, text=True, timeout=180, check=False)
    if result.returncode not in (0, 2) or not result.stdout.strip():
        print('Rector execution failed; inspect stderr', file=sys.stderr)
        print(result.stderr[-2000:], file=sys.stderr)
        return 2
    report = json.loads(result.stdout)
    if report['totals']['errors']:
        print('Rector reported errors', file=sys.stderr)
        return 2
    failures = compare(root, report['file_diffs'], json.loads((root / 'config/rector-baseline.json').read_text()))
    print(json.dumps({'new_or_changed_debt': failures, 'existing_proposals': len(report['file_diffs']),
                      'baseline_limit': 6, 'mode': 'dry-run; original files unchanged'}))
    return int(bool(failures))


if __name__ == '__main__':
    raise SystemExit(main())
