<?php

namespace App\Http\Controllers;

use App\Services\CatalogSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly CatalogSearch $catalog) {}

    /**
     * Global, public search across the catalog entities (clubs, events, news,
     * resources). Results are limited to the same publicly-visible records as
     * the public catalog pages. Returns JSON consumed by the command palette.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $groups = $this->catalog->search((string) $request->string('q'));

        return response()->json(['groups' => $groups]);
    }
}
