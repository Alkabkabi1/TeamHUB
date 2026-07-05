#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REPLACEMENTS = [
    ("$this->route('club')", "$this->route('workspace')"),
    ('$this->route("club")', '$this->route("workspace")'),
    ("$this->route('committee')", "$this->route('project')"),
    ('$this->route("committee")', '$this->route("project")'),
    ("->route('club')", "->route('workspace')"),
    ("->route('committee')", "->route('project')"),
]

for path in (ROOT / 'app').rglob('*.php'):
    text = path.read_text(encoding='utf-8')
    updated = text
    for old, new in REPLACEMENTS:
        updated = updated.replace(old, new)
    if updated != text:
        path.write_text(updated, encoding='utf-8')
        print(path.relative_to(ROOT))

print('done')
