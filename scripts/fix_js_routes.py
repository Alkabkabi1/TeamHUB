#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / 'resources' / 'js'

REPLACEMENTS = [
    ("@/routes/clubs/join", "@/routes/workspaces/join"),
    ("@/routes/clubs", "@/routes/workspaces"),
    ("@/routes/committees", "@/routes/projects"),
    ("ClubMemberController", "WorkspaceMemberController"),
    ("ClubReportController", "WorkspaceReportController"),
    ("CommitteeMemberController", "ProjectMemberController"),
    ("CommitteeMembershipController", "ProjectMembershipController"),
    ("CommitteeReportController", "ProjectReportController"),
    ("NewsController", "ProjectUpdateController"),
]

for path in ROOT.rglob('*'):
    if path.suffix not in {'.svelte', '.ts'}:
        continue
    text = path.read_text(encoding='utf-8')
    updated = text
    for old, new in REPLACEMENTS:
        updated = updated.replace(old, new)
    if updated != text:
        path.write_text(updated, encoding='utf-8')
        print(path.relative_to(ROOT))

print('done')
