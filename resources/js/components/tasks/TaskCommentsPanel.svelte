<script lang="ts">
    import InputError from '@/components/InputError.svelte';
    import { formatDate, t } from '@/lib/i18n.svelte';

    type CommentItem = {
        id: number;
        body: string;
        author_name: string;
        created_at: string | null;
        can_delete: boolean;
        delete_url: string;
    };

    type CommentForm = {
        body: string;
        processing: boolean;
        errors: Record<string, string | undefined>;
    };

    let {
        comments = [],
        canComment = false,
        commentForm,
        onSubmit,
        onCommentKeydown,
        onDeleteComment,
    }: {
        comments?: CommentItem[];
        canComment?: boolean;
        commentForm: CommentForm;
        onSubmit: (event: SubmitEvent) => void;
        onCommentKeydown: (event: KeyboardEvent) => void;
        onDeleteComment: (deleteUrl: string) => void;
    } = $props();
</script>

<div
    class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)]"
>
    <h2 class="text-lg font-medium text-black dark:text-white">
        {t('tasks.comments_title')}
    </h2>

    {#if canComment}
        <form onsubmit={onSubmit} class="mt-4 space-y-3">
            <textarea
                name="body"
                bind:value={commentForm.body}
                rows="4"
                placeholder={t('tasks.comment_placeholder')}
                class="w-full rounded-[10px] border border-black/15 bg-white px-4 py-3 text-sm outline-none focus:border-brand dark:border-white/10 dark:bg-[#0f172a] dark:text-white"
                aria-keyshortcuts="Enter"
                onkeydown={onCommentKeydown}
            ></textarea>
            <InputError message={commentForm.errors.body} />
            <button
                type="submit"
                disabled={commentForm.processing}
                class="rounded-full bg-brand px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-dark disabled:opacity-60"
            >
                {t('tasks.comment_submit')}
            </button>
        </form>
    {/if}

    <div class="mt-5 space-y-3">
        {#if comments.length === 0}
            <p class="text-sm text-[#7e7e7e] dark:text-[#94a3b8]">
                {t('tasks.comments_empty')}
            </p>
        {:else}
            {#each comments as comment (comment.id)}
                <div
                    class="rounded-[14px] border border-black/10 p-4 dark:border-white/10"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div class="space-y-1 text-start">
                            <p
                                class="text-sm font-medium text-black dark:text-white"
                            >
                                {comment.author_name}
                            </p>
                            <p
                                class="text-xs text-[#9a9a9a] dark:text-[#94a3b8]"
                            >
                                {comment.created_at
                                    ? formatDate(comment.created_at, {
                                          year: 'numeric',
                                          month: 'short',
                                          day: 'numeric',
                                          hour: '2-digit',
                                          minute: '2-digit',
                                      })
                                    : ''}
                            </p>
                        </div>
                        {#if comment.can_delete}
                            <button
                                type="button"
                                onclick={() =>
                                    onDeleteComment(comment.delete_url)}
                                class="rounded-full bg-black/5 px-3 py-1.5 text-xs font-medium text-[#5f5f5f] transition-colors hover:bg-black/10 dark:bg-white/10 dark:text-[#cbd5e1] dark:hover:bg-white/15"
                            >
                                {t('tasks.comment_delete')}
                            </button>
                        {/if}
                    </div>
                    <p
                        class="mt-3 whitespace-pre-wrap text-sm text-[#5f5f5f] dark:text-[#cbd5e1]"
                    >
                        {comment.body}
                    </p>
                </div>
            {/each}
        {/if}
    </div>
</div>
