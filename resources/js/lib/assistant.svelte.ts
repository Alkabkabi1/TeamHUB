/**
 * Shared open-state for the AI assistant chat panel. A single
 * `<AssistantPanel />` instance lives in the layout while the launcher button
 * (next to the accessibility options) toggles it through this store.
 */
export const assistant = $state({ open: false });

export function openAssistant(): void {
    assistant.open = true;
}
