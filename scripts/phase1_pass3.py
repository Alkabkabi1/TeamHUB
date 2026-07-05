#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REPLACEMENTS = [
    ("'club' =>", "'workspace' =>"),
    ('"club" =>', '"workspace" =>'),
    ("'committee' =>", "'project' =>"),
    ('"committee" =>', '"project" =>'),
    ("route('workspaces')", "route('workspaces.show'"),  # noop guard - skip bad
]

def main():
    changed = 0
    for path in (ROOT / 'tests').rglob('*.php'):
        text = path.read_text(encoding='utf-8')
        updated = text
        for old, new in REPLACEMENTS[:4]:
            updated = updated.replace(old, new)
        if updated != text:
            path.write_text(updated, encoding='utf-8')
            changed += 1
    print(f'Updated {changed} test files')

if __name__ == '__main__':
    main()
