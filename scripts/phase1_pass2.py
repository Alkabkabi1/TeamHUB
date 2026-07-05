#!/usr/bin/env python3
"""Second pass: route prefixes and param renames."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

REPLACEMENTS = [
    ("route('committees.", "route('projects."),
    ('route("committees.', 'route("projects.'),
    ("route('clubs.", "route('workspaces."),
    ('route("clubs.', 'route("workspaces.'),
    ("$club", "$workspace"),
    ("$committee", "$project"),
    ("defaultClub()", "defaultWorkspace()"),
    ("defaultClub", "defaultWorkspace"),
    ("committee_name", "project_name"),
    ("'committees.sort_options'", "'project.sort_options'"),
    ("committees.sort_options", "project.sort_options"),
]

SKIP = {"vendor", "node_modules", "scripts"}


def main() -> None:
    changed = 0
    for d in ["app", "tests", "database"]:
        base = ROOT / d
        if not base.exists():
            continue
        for path in base.rglob("*.php"):
            if SKIP.intersection(path.parts):
                continue
            original = path.read_text(encoding="utf-8")
            updated = original
            for old, new in REPLACEMENTS:
                updated = updated.replace(old, new)
            if updated != original:
                path.write_text(updated, encoding="utf-8")
                changed += 1
    print(f"Pass 2 updated {changed} files")


if __name__ == "__main__":
    main()
