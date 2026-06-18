/**
 * Shared open-state for the global search command palette. A single
 * `<GlobalSearch />` instance lives in the layout while several triggers
 * (desktop topbar, mobile header, ⌘K) toggle it through this store.
 */
export const globalSearch = $state({ open: false });

export function openGlobalSearch(): void {
    globalSearch.open = true;
}
