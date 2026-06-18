<?php

namespace App\Ai;

/**
 * Accumulates write-tool confirmation payloads during a single request so the
 * SSE controller can emit them as "tool_confirm" events after the text stream.
 *
 * Registered as a scoped singleton so Octane creates a fresh instance per
 * request rather than leaking state between requests.
 */
class PendingActionService
{
    /** @var list<array{id: string, summary: string, changes: list<string>}> */
    private array $actions = [];

    /**
     * Register a pending action produced by a write tool.
     *
     * @param  list<string>  $changes
     */
    public function add(string $id, string $summary, array $changes): void
    {
        $this->actions[] = compact('id', 'summary', 'changes');
    }

    /**
     * Return all pending actions registered in this request.
     *
     * @return list<array{id: string, summary: string, changes: list<string>}>
     */
    public function all(): array
    {
        return $this->actions;
    }
}
