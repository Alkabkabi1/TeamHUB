<?php

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\CatalogSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Free-text search across the public catalog (clubs, events, news, resources),
 * returning the same publicly-visible records as the site's command palette.
 */
class SearchCatalog extends AssistantTool
{
    public function __construct(?User $user, private readonly CatalogSearch $catalog)
    {
        parent::__construct($user);
    }

    public function description(): Stringable|string
    {
        return 'Search the public catalog of clubs, events, news articles, and resources by keyword. '
            .'Use this when the user asks to find or look up something by name or topic.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->json($this->catalog->search((string) ($request['query'] ?? '')));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The search keywords, e.g. a club name, event topic, or news subject.')
                ->required(),
        ];
    }
}
