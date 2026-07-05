#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / 'resources' / 'js'

URL_REPLACEMENTS = [
    ('/clubs/', '/workspaces/'),
    ('/committees/', '/projects/'),
    ('/news/', '/updates/'),
]

for path in ROOT.rglob('*'):
    if path.suffix not in {'.svelte', '.ts'}:
        continue
    text = path.read_text(encoding='utf-8')
    updated = text
    for old, new in URL_REPLACEMENTS:
        updated = updated.replace(old, new)
    if updated != text:
        path.write_text(updated, encoding='utf-8')
        print(path.relative_to(ROOT))

print('done')
