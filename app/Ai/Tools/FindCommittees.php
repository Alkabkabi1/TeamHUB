<?php

namespace App\Ai\Tools;

use App\Models\Committee;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * List or search committees (sub-groups within clubs) across the platform —
 * optionally by keyword or parent club. Archived committees are excluded.
 */
class FindCommittees extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List or search committees (sub-groups within clubs) by keyword and/or parent club. '
            .'Use this when the user wants to find a committee to join or browse all available committees.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) ($request['search'] ?? ''));
        $clubName = trim((string) ($request['club'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 15), 1), 30);

        $committees = Committee::query()
            ->with('club:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($clubName !== '', fn ($q) => $q->whereHas('club', fn ($c) => $c->where('name', 'like', "%{$clubName}%")))
            ->orderBy('club_id')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Committee $committee): array => [
                'id' => $committee->id,
                'name' => $committee->name,
                'club' => $committee->club?->name,
                'description' => $committee->description,
                'status' => $committee->status?->value,
                'membersCount' => $committee->memberships()->where('status', 'approved')->count(),
                'url' => route('committees.show', ['club' => $committee->club_id, 'committee' => $committee->id]),
            ])
            ->all();

        return $this->json(['committees' => $committees]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Optional keyword to match against committee name or description (e.g. "تقنية", "برمجة").'),
            'club' => $schema->string()
                ->description('Optional parent club name to list only that club\'s committees.'),
            'limit' => $schema->integer()->min(1)->max(30)
                ->description('Maximum number of committees to return (default 15).'),
        ];
    }
}
