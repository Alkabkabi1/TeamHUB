<script lang="ts">
    import {
        ArrowUp01Icon,
        Cancel01Icon,
        CheckmarkCircle01Icon,
        SparklesIcon,
        StarsIcon,
        TickDouble02Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { page, router } from '@inertiajs/svelte';
    import { tick } from 'svelte';
    import AssistantConfirmController from '@/actions/App/Http/Controllers/AssistantConfirmController';
    import AssistantController from '@/actions/App/Http/Controllers/AssistantController';
    import AssistantSuggestionController from '@/actions/App/Http/Controllers/AssistantSuggestionController';
    import { Sheet, SheetContent } from '@/components/ui/sheet';
    import { Spinner } from '@/components/ui/spinner';
    import { assistant } from '@/lib/assistant.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { renderMarkdown } from '@/lib/markdown';

    type PendingConfirmation = {
        id: string;
        summary: string;
        changes: string[];
        state: 'pending' | 'confirming' | 'confirmed' | 'cancelled' | 'error';
        resultMessage?: string;
    };

    type ToolActivity = {
        id: string;
        label: string;
        done: boolean;
        ok: boolean;
    };

    /**
     * One reasoning/tool round: the chain-of-thought text and any tool calls
     * the model made before it next produced answer text. A fresh part is
     * created each time the model returns to thinking after writing text, so
     * interleaved reasoning and tool use render as their own collapsibles.
     */
    type ThinkingPart = {
        kind: 'thinking';
        reasoning: string;
        activities: ToolActivity[];
        open: boolean;
        done: boolean;
    };

    /** A run of answer text (markdown) the model streamed. */
    type TextPart = {
        kind: 'text';
        content: string;
    };

    type MessagePart = ThinkingPart | TextPart;

    type ChatMessage = {
        role: 'user' | 'assistant';
        content: string;
        parts: MessagePart[];
        confirmations: PendingConfirmation[];
        hidden?: boolean;
    };

    const direction = $derived((page.props.direction as string) ?? 'rtl');
    const side = $derived(direction === 'rtl' ? 'left' : 'right');

    let input = $state('');
    let loading = $state(false);
    let conversationId = $state<string | null>(null);
    let messages = $state<ChatMessage[]>([]);
    let suggestions = $state<string[]>([]);
    let scrollEl = $state<HTMLDivElement | null>(null);
    let inputEl = $state<HTMLTextAreaElement | null>(null);

    /**
     * Lazily fetch the starter-prompt suggestions the first time the panel is
     * opened with an empty conversation. They are tailored server-side to the
     * current user and the data available to them.
     */
    $effect(() => {
        if (
            assistant.open &&
            suggestions.length === 0 &&
            messages.length === 0
        ) {
            void loadSuggestions();
        }
    });

    async function loadSuggestions(): Promise<void> {
        try {
            const response = await fetch(AssistantSuggestionController.url(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = (await response.json()) as { suggestions: string[] };
            suggestions = data.suggestions ?? [];
        } catch {
            // Suggestions are a nicety; silently skip them on failure.
        }
    }

    /**
     * Read Laravel's XSRF-TOKEN cookie so the raw fetch POST passes CSRF
     * verification (the value is URL-encoded in the cookie).
     */
    function xsrfToken(): string {
        const match = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='));

        return match ? decodeURIComponent(match.split('=')[1]) : '';
    }

    /**
     * Grow the composer with its content, up to a sensible cap.
     */
    function autoGrow(): void {
        if (!inputEl) {
            return;
        }

        inputEl.style.height = 'auto';
        inputEl.style.height = `${Math.min(inputEl.scrollHeight, 160)}px`;
    }

    async function scrollToBottom(): Promise<void> {
        await tick();
        scrollEl?.scrollTo({ top: scrollEl.scrollHeight, behavior: 'smooth' });
    }

    /**
     * Return the open thinking part to append reasoning/tool activity to,
     * creating a fresh one when the last part isn't a still-open thinking
     * block. This is what turns interleaved reasoning into separate
     * collapsibles instead of growing one shared block.
     */
    function activeThinkingPart(message: ChatMessage): ThinkingPart {
        const last = message.parts[message.parts.length - 1];

        if (last && last.kind === 'thinking' && !last.done) {
            return last;
        }

        const part: ThinkingPart = {
            kind: 'thinking',
            reasoning: '',
            activities: [],
            open: true,
            done: false,
        };
        message.parts.push(part);

        return part;
    }

    /**
     * Return the text part to append answer deltas to. Switching to text marks
     * any open thinking part done and collapses it, so its reasoning folds away
     * as the answer for that round begins.
     */
    function activeTextPart(message: ChatMessage): TextPart {
        const last = message.parts[message.parts.length - 1];

        if (last && last.kind === 'text') {
            return last;
        }

        for (const part of message.parts) {
            if (part.kind === 'thinking') {
                part.done = true;
                part.open = false;
            }
        }

        const part: TextPart = { kind: 'text', content: '' };
        message.parts.push(part);

        return part;
    }

    /** Mark a tool activity finished once its result event arrives. */
    function markToolResult(
        message: ChatMessage,
        id: string,
        ok: boolean,
    ): void {
        for (const part of message.parts) {
            if (part.kind === 'thinking') {
                const activity = part.activities.find((a) => a.id === id);

                if (activity) {
                    activity.done = true;
                    activity.ok = ok;

                    return;
                }
            }
        }
    }

    /** Whether the assistant produced any answer text (vs only reasoning). */
    function hasAnswerText(message: ChatMessage): boolean {
        return message.parts.some(
            (part) => part.kind === 'text' && part.content !== '',
        );
    }

    function startNewChat(): void {
        if (loading) {
            return;
        }

        messages = [];
        conversationId = null;
        input = '';
        autoGrow();
    }

    /**
     * Send a starter-prompt suggestion as if the user typed and submitted it.
     */
    function sendSuggestion(text: string): void {
        if (loading) {
            return;
        }

        input = text;
        void send();
    }

    async function send(overrideText?: string): Promise<void> {
        const text = overrideText ?? input.trim();

        if (text === '' || loading) {
            return;
        }

        messages = [
            ...messages,
            {
                role: 'user',
                content: text,
                parts: [],
                confirmations: [],
                hidden: overrideText !== undefined,
            },
            {
                role: 'assistant',
                content: '',
                parts: [],
                confirmations: [],
            },
        ];
        const assistantIndex = messages.length - 1;

        if (overrideText === undefined) {
            input = '';
            autoGrow();
        }

        loading = true;
        void scrollToBottom();

        try {
            const response = await fetch(AssistantController.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-XSRF-TOKEN': xsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId,
                }),
            });

            if (!response.ok || !response.body) {
                const body = await response.text().catch(() => '');

                throw new Error(
                    `HTTP ${response.status} ${response.statusText} ${body}`.trim(),
                );
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            // Parse the Server-Sent Events stream incrementally, handling
            // text deltas and the trailing conversation-id event.
            for (;;) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });
                const chunks = buffer.split('\n\n');
                buffer = chunks.pop() ?? '';

                for (const chunk of chunks) {
                    const line = chunk.trim();

                    if (!line.startsWith('data:')) {
                        continue;
                    }

                    const payload = line.slice(5).trim();

                    if (payload === '' || payload === '[DONE]') {
                        continue;
                    }

                    const event = JSON.parse(payload) as
                        | { type: 'reasoning'; text: string }
                        | { type: 'delta'; text: string }
                        | { type: 'error'; message: string }
                        | { type: 'conversation'; id: string | null }
                        | { type: 'tool_call'; id: string; label: string }
                        | { type: 'tool_result'; id: string; ok: boolean }
                        | {
                              type: 'tool_confirm';
                              id: string;
                              summary: string;
                              changes: string[];
                          };

                    const message = messages[assistantIndex];

                    if (event.type === 'error') {
                        console.error(
                            '[assistant] server error:',
                            event.message,
                        );
                        activeTextPart(message).content =
                            event.message || t('assistant.error');
                    } else if (event.type === 'reasoning') {
                        activeThinkingPart(message).reasoning += event.text;
                        void scrollToBottom();
                    } else if (event.type === 'delta') {
                        activeTextPart(message).content += event.text;
                        void scrollToBottom();
                    } else if (event.type === 'tool_call') {
                        activeThinkingPart(message).activities.push({
                            id: event.id,
                            label: event.label,
                            done: false,
                            ok: true,
                        });
                        void scrollToBottom();
                    } else if (event.type === 'tool_result') {
                        markToolResult(message, event.id, event.ok);
                    } else if (event.type === 'tool_confirm') {
                        message.confirmations.push({
                            id: event.id,
                            summary: event.summary,
                            changes: event.changes,
                            state: 'pending',
                        });
                        void scrollToBottom();
                    } else if (event.type === 'conversation' && event.id) {
                        conversationId = event.id;
                    }
                }
            }

            if (!hasAnswerText(messages[assistantIndex])) {
                activeTextPart(messages[assistantIndex]).content =
                    t('assistant.error');
            }
        } catch (error) {
            console.error('[assistant] request failed:', error);

            if (!hasAnswerText(messages[assistantIndex])) {
                activeTextPart(messages[assistantIndex]).content =
                    t('assistant.error');
            }
        } finally {
            loading = false;
            void scrollToBottom();
        }
    }

    async function confirmAction(
        msgIndex: number,
        confirmIndex: number,
        confirmed: boolean,
    ): Promise<void> {
        const confirmation = messages[msgIndex].confirmations[confirmIndex];

        if (!confirmed) {
            messages[msgIndex].confirmations[confirmIndex].state = 'cancelled';
            void send(t('assistant.confirmation_cancelled_message'));

            return;
        }

        messages[msgIndex].confirmations[confirmIndex].state = 'confirming';

        try {
            const response = await fetch(
                AssistantConfirmController.url(confirmation.id),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': xsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            const result = (await response.json()) as {
                success: boolean;
                message: string;
            };

            messages[msgIndex].confirmations[confirmIndex].state =
                result.success ? 'confirmed' : 'error';
            messages[msgIndex].confirmations[confirmIndex].resultMessage =
                result.message;

            void send(
                result.success
                    ? `${t('assistant.confirmation_success_prefix')}: ${result.message}`
                    : `${t('assistant.confirmation_failure_prefix')}: ${result.message}`,
            );
        } catch {
            messages[msgIndex].confirmations[confirmIndex].state = 'error';
            messages[msgIndex].confirmations[confirmIndex].resultMessage =
                t('assistant.error');
            void send(t('assistant.confirmation_connection_error'));
        }

        void scrollToBottom();
    }

    function onKeydown(event: KeyboardEvent): void {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            void send();
        }
    }

    /**
     * Svelte action that intercepts clicks on links rendered inside `{@html}`
     * markdown output. Internal links (stamped with data-inertia-link by the
     * renderer) use Inertia's router so navigation is a soft SPA visit instead
     * of a full page reload. External links fall through to the browser default.
     */
    function inertiaLinks(node: HTMLElement): { destroy(): void } {
        function handleClick(event: MouseEvent): void {
            const link = (
                event.target as HTMLElement
            ).closest<HTMLAnchorElement>('a[data-inertia-link]');

            if (!link) {
                return;
            }

            // Let the browser handle file downloads natively — intercepting
            // them with router.visit() would send an XHR that receives a
            // binary response instead of an Inertia page.
            const path = new URL(link.href).pathname;

            if (path.endsWith('/download')) {
                return;
            }

            event.preventDefault();
            // Soft-navigate, then close the panel once the new page has loaded
            // so the user lands on the page the assistant pointed them to.
            router.visit(link.href, {
                onSuccess: () => {
                    assistant.open = false;
                },
            });
        }

        node.addEventListener('click', handleClick);

        return {
            destroy(): void {
                node.removeEventListener('click', handleClick);
            },
        };
    }
</script>

<Sheet bind:open={assistant.open}>
    <SheetContent
        {side}
        class="flex w-full flex-col gap-0 !max-w-none bg-white p-0 text-foreground !w-[92vw] sm:!w-[30rem] lg:!w-[46rem]"
        showCloseButton={false}
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b px-4 py-3">
            <div class="flex items-center gap-2">
                <span
                    class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary"
                >
                    <HugeiconsIcon
                        icon={StarsIcon}
                        class="size-5"
                        strokeWidth={2}
                    />
                </span>
                <div class="text-start">
                    <p class="text-sm font-semibold leading-tight">
                        {t('assistant.title')}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {t('assistant.subtitle')}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                {#if messages.length > 0}
                    <button
                        type="button"
                        onclick={startNewChat}
                        class="rounded-md px-2 py-1 text-xs text-muted-foreground transition hover:bg-muted disabled:opacity-50"
                        disabled={loading}
                    >
                        {t('assistant.new_chat')}
                    </button>
                {/if}
                <button
                    type="button"
                    onclick={() => (assistant.open = false)}
                    aria-label={t('assistant.close')}
                    class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted"
                >
                    <HugeiconsIcon
                        icon={Cancel01Icon}
                        class="size-5"
                        strokeWidth={2}
                    />
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div
            bind:this={scrollEl}
            class="flex-1 space-y-3 overflow-y-auto px-4 py-4"
        >
            {#if messages.length === 0}
                <div
                    class="flex h-full flex-col items-center justify-center gap-2 text-center text-muted-foreground"
                >
                    <span
                        class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <HugeiconsIcon
                            icon={StarsIcon}
                            class="size-6"
                            strokeWidth={2}
                        />
                    </span>
                    <p class="text-base font-medium text-foreground">
                        {t('assistant.greeting')}
                    </p>
                    <p class="max-w-xs text-sm">
                        {t('assistant.greeting_hint')}
                    </p>

                    <!-- Starter prompts: tailored to the user, sent on click. -->
                    {#if suggestions.length > 0}
                        <div
                            class="mt-3 flex max-w-md flex-wrap justify-center gap-2"
                        >
                            {#each suggestions as suggestion (suggestion)}
                                <button
                                    type="button"
                                    onclick={() => sendSuggestion(suggestion)}
                                    disabled={loading}
                                    class="rounded-full border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10 disabled:opacity-50"
                                >
                                    {suggestion}
                                </button>
                            {/each}
                        </div>
                    {/if}
                </div>
            {/if}

            {#each messages as message, index (index)}
                {#if !(message.role === 'user' && message.hidden)}
                    <div
                        class="flex {message.role === 'user'
                            ? 'justify-end'
                            : 'justify-start'}"
                    >
                        <div
                            class="max-w-[88%] rounded-2xl px-3.5 py-2.5 text-base leading-relaxed sm:text-[15px] {message.role ===
                            'user'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-foreground'}"
                        >
                            {#if message.role === 'assistant'}
                                {#each message.parts as part, partIndex (partIndex)}
                                    {#if part.kind === 'thinking'}
                                        {#if part.reasoning || part.activities.length > 0}
                                            <!-- Collapsible thinking + tool activity for this round; collapses once its answer begins -->
                                            <div class="mb-1.5">
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        (part.open =
                                                            !part.open)}
                                                    class="flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground"
                                                >
                                                    {#if loading && !part.done}
                                                        <Spinner
                                                            class="size-3.5"
                                                        />
                                                    {:else}
                                                        <HugeiconsIcon
                                                            icon={SparklesIcon}
                                                            class="size-3.5"
                                                            strokeWidth={2}
                                                        />
                                                    {/if}
                                                    {t('assistant.reasoning')}
                                                    <span
                                                        class="transition-transform {part.open
                                                            ? 'rotate-180'
                                                            : ''}">▾</span
                                                    >
                                                </button>
                                                {#if part.open}
                                                    <div
                                                        class="mt-1 space-y-1 border-s-2 border-black/10 ps-2 text-xs leading-relaxed text-muted-foreground"
                                                    >
                                                        {#each part.activities as activity (activity.id)}
                                                            <div
                                                                class="flex items-center gap-1.5"
                                                            >
                                                                {#if !activity.done}
                                                                    <Spinner
                                                                        class="size-3 shrink-0"
                                                                    />
                                                                {:else}
                                                                    <HugeiconsIcon
                                                                        icon={activity.ok
                                                                            ? CheckmarkCircle01Icon
                                                                            : Cancel01Icon}
                                                                        class="size-3 shrink-0 {activity.ok
                                                                            ? 'text-green-600'
                                                                            : 'text-destructive'}"
                                                                        strokeWidth={2}
                                                                    />
                                                                {/if}
                                                                <span
                                                                    >{activity.label}</span
                                                                >
                                                            </div>
                                                        {/each}
                                                        {#if part.reasoning}
                                                            <div
                                                                class="whitespace-pre-wrap"
                                                            >
                                                                {part.reasoning}
                                                            </div>
                                                        {/if}
                                                    </div>
                                                {/if}
                                            </div>
                                        {/if}
                                    {:else if part.content !== ''}
                                        <div class="md" use:inertiaLinks>
                                            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                                            {@html renderMarkdown(part.content)}
                                        </div>
                                    {/if}
                                {/each}

                                {#if message.parts.length === 0 && loading}
                                    <span
                                        class="flex items-center gap-1 text-muted-foreground"
                                    >
                                        <Spinner class="size-4" />
                                        {t('assistant.thinking')}
                                    </span>
                                {/if}
                            {:else}
                                <span class="whitespace-pre-wrap"
                                    >{message.content}</span
                                >
                            {/if}

                            {#if message.role === 'assistant' && message.confirmations.length > 0}
                                {#each message.confirmations as confirmation, cIdx (confirmation.id)}
                                    <div
                                        class="mt-2 rounded-xl border bg-white p-3 text-sm shadow-sm"
                                    >
                                        {#if confirmation.state === 'pending' || confirmation.state === 'confirming'}
                                            <p
                                                class="mb-1.5 font-medium text-foreground"
                                            >
                                                {confirmation.summary}
                                            </p>
                                            <ul
                                                class="mb-3 space-y-0.5 text-muted-foreground"
                                            >
                                                {#each confirmation.changes as change, changeIdx (changeIdx)}
                                                    <li
                                                        class="flex items-start gap-1.5"
                                                    >
                                                        <span
                                                            class="mt-0.5 text-primary"
                                                            >•</span
                                                        >
                                                        <span>{change}</span>
                                                    </li>
                                                {/each}
                                            </ul>
                                            <div class="flex gap-2">
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        void confirmAction(
                                                            index,
                                                            cIdx,
                                                            true,
                                                        )}
                                                    disabled={confirmation.state ===
                                                        'confirming'}
                                                    class="flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition hover:opacity-90 disabled:opacity-50"
                                                >
                                                    {#if confirmation.state === 'confirming'}
                                                        <Spinner
                                                            class="size-3.5"
                                                        />
                                                    {:else}
                                                        <HugeiconsIcon
                                                            icon={TickDouble02Icon}
                                                            class="size-3.5"
                                                            strokeWidth={2}
                                                        />
                                                    {/if}
                                                    {t('assistant.confirm')}
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        void confirmAction(
                                                            index,
                                                            cIdx,
                                                            false,
                                                        )}
                                                    disabled={confirmation.state ===
                                                        'confirming'}
                                                    class="rounded-lg border px-3 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-muted disabled:opacity-50"
                                                >
                                                    {t('assistant.cancel')}
                                                </button>
                                            </div>
                                        {:else if confirmation.state === 'confirmed'}
                                            <div
                                                class="flex items-center gap-2 text-green-700"
                                            >
                                                <HugeiconsIcon
                                                    icon={CheckmarkCircle01Icon}
                                                    class="size-4 shrink-0"
                                                    strokeWidth={2}
                                                />
                                                <span
                                                    >{confirmation.resultMessage}</span
                                                >
                                            </div>
                                        {:else if confirmation.state === 'cancelled'}
                                            <p class="text-muted-foreground">
                                                {t(
                                                    'assistant.confirmation_cancelled',
                                                )}
                                            </p>
                                        {:else if confirmation.state === 'error'}
                                            <div
                                                class="flex items-center gap-2 text-destructive"
                                            >
                                                <HugeiconsIcon
                                                    icon={Cancel01Icon}
                                                    class="size-4 shrink-0"
                                                    strokeWidth={2}
                                                />
                                                <span
                                                    >{confirmation.resultMessage ??
                                                        t(
                                                            'assistant.error',
                                                        )}</span
                                                >
                                            </div>
                                        {/if}
                                    </div>
                                {/each}
                            {/if}
                        </div>
                    </div>
                {/if}
            {/each}
        </div>

        <!-- Composer -->
        <div class="border-t p-3">
            <div
                class="flex items-end gap-2 rounded-2xl border bg-white px-3 py-2 focus-within:ring-2 focus-within:ring-primary/40"
            >
                <textarea
                    bind:this={inputEl}
                    bind:value={input}
                    oninput={autoGrow}
                    onkeydown={onKeydown}
                    rows="2"
                    placeholder={t('assistant.placeholder')}
                    class="max-h-40 min-h-[1.5rem] flex-1 resize-none bg-transparent text-base outline-none placeholder:text-muted-foreground sm:text-[15px]"
                ></textarea>
                <button
                    type="button"
                    onclick={() => send()}
                    disabled={loading || input.trim() === ''}
                    aria-label={t('assistant.send')}
                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground transition hover:opacity-90 disabled:opacity-40"
                >
                    {#if loading}
                        <Spinner class="size-4" />
                    {:else}
                        <HugeiconsIcon
                            icon={ArrowUp01Icon}
                            class="size-5"
                            strokeWidth={2}
                        />
                    {/if}
                </button>
            </div>
        </div>
    </SheetContent>
</Sheet>

<style>
    /* Minimal styling for sanitized markdown rendered via {@html}. */
    .md :global(p) {
        margin: 0.25rem 0;
    }
    .md :global(p:first-child) {
        margin-top: 0;
    }
    .md :global(p:last-child) {
        margin-bottom: 0;
    }
    .md :global(ul),
    .md :global(ol) {
        margin: 0.25rem 0;
        padding-inline-start: 1.25rem;
    }
    .md :global(ul) {
        list-style: disc;
    }
    .md :global(ol) {
        list-style: decimal;
    }
    .md :global(li) {
        margin: 0.125rem 0;
    }
    .md :global(a) {
        color: var(--color-primary);
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .md :global(strong) {
        font-weight: 600;
    }
    .md :global(h1),
    .md :global(h2),
    .md :global(h3),
    .md :global(h4) {
        font-weight: 600;
        margin: 0.5rem 0 0.25rem;
    }
    .md :global(code) {
        background: rgba(0, 0, 0, 0.06);
        padding: 0.1em 0.35em;
        border-radius: 0.25rem;
        font-size: 0.9em;
    }
    .md :global(pre) {
        background: rgba(0, 0, 0, 0.06);
        padding: 0.625rem 0.75rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin: 0.375rem 0;
    }
    .md :global(pre code) {
        background: transparent;
        padding: 0;
    }
    .md :global(blockquote) {
        border-inline-start: 3px solid rgba(0, 0, 0, 0.12);
        padding-inline-start: 0.75rem;
        margin: 0.375rem 0;
        color: rgba(0, 0, 0, 0.6);
    }
</style>
