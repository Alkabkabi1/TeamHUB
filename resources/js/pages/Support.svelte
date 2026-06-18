<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { fade, fly } from 'svelte/transition';
    import { store as contactStore } from '@/actions/App/Http/Controllers/ContactController';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import FaqItem from '@/components/FaqItem.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import InputError from '@/components/InputError.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { Textarea } from '@/components/ui/textarea';
    import { t } from '@/lib/i18n.svelte';

    // Tracked by question text (not index) so the open answer stays aligned
    // with its card while the list is being filtered.
    let openFaq = $state<string | null>(null);
    let faqSearch = $state('');

    const FAQ_INITIAL = 3;
    let visibleCount = $state(FAQ_INITIAL);

    function toggleFaq(question: string) {
        openFaq = openFaq === question ? null : question;
    }

    const faqs = $derived([
        { q: t('support.faq.q1'), a: t('support.faq.a1') },
        { q: t('support.faq.q2'), a: t('support.faq.a2') },
        { q: t('support.faq.q3'), a: t('support.faq.a3') },
        { q: t('support.faq.q4'), a: t('support.faq.a4') },
    ]);

    const isSearching = $derived(faqSearch.trim() !== '');

    const filteredFaqs = $derived(
        !isSearching
            ? faqs
            : faqs.filter((faq) => {
                  const query = faqSearch.trim().toLowerCase();

                  return (
                      faq.q.toLowerCase().includes(query) ||
                      faq.a.toLowerCase().includes(query)
                  );
              }),
    );

    // While searching, show every match; otherwise reveal progressively via
    // the "show more" button to mirror the Figma design.
    const displayedFaqs = $derived(
        isSearching ? filteredFaqs : filteredFaqs.slice(0, visibleCount),
    );
    const hasMore = $derived(!isSearching && visibleCount < faqs.length);

    const contactForm = useForm({ name: '', email: '', message: '' });

    function submitContact(event: SubmitEvent) {
        event.preventDefault();
        contactForm.post(contactStore.url(), {
            preserveScroll: true,
            onSuccess: () => contactForm.reset(),
        });
    }
</script>

<AppHead title={t('support.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <HeroBanner
            ariaLabel={t('support.hero_title')}
            title={t('support.hero_title')}
            subtitle={t('support.hero_subtitle')}
        />

        <form
            onsubmit={(event) => event.preventDefault()}
            class="flex items-center gap-3"
        >
            <SearchField
                bind:value={faqSearch}
                placeholder={t('support.faq_search')}
                ariaLabel={t('support.faq_search')}
            />
            <button
                type="submit"
                class="min-h-11 rounded-full bg-brand px-8 text-sm font-medium text-white transition-colors hover:bg-brand-dark"
            >
                {t('app.search')}
            </button>
        </form>

        <section class="flex flex-col gap-4">
            <SectionHeader title={t('support.faq_title')} />

            {#if filteredFaqs.length === 0}
                <EmptyState class="px-6" message={t('support.no_results')} />
            {:else}
                {#each displayedFaqs as faq, i (faq.q)}
                    <div in:fly={{ y: 12, duration: 250, delay: i * 40 }}>
                        <FaqItem
                            question={faq.q}
                            answer={faq.a}
                            open={openFaq === faq.q}
                            onToggle={() => toggleFaq(faq.q)}
                        />
                    </div>
                {/each}

                {#if hasMore}
                    <div class="mt-2 flex justify-center">
                        <button
                            type="button"
                            onclick={() => (visibleCount = faqs.length)}
                            class="rounded-full bg-brand/50 px-10 py-2.5 text-base font-medium text-white transition-colors hover:bg-brand/70"
                        >
                            {t('app.show_more')}
                        </button>
                    </div>
                {/if}
            {/if}
        </section>

        <section class="flex flex-col gap-4">
            <SectionHeader title={t('support.contact_us')} />
            <form
                onsubmit={submitContact}
                class="flex flex-col gap-4 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
            >
                <p class="text-sm text-black">{t('support.send_inquiry')}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="name" class="text-[13px] text-[#7e7e7e]">
                            {t('support.name')}
                        </Label>
                        <Input
                            id="name"
                            name="name"
                            bind:value={contactForm.name}
                            placeholder={t('support.name')}
                        />
                        <InputError message={contactForm.errors.name} />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="email" class="text-[13px] text-[#7e7e7e]">
                            {t('support.email')}
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            dir="ltr"
                            bind:value={contactForm.email}
                            placeholder={t('support.email')}
                        />
                        <InputError message={contactForm.errors.email} />
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="message" class="text-[13px] text-[#7e7e7e]">
                        {t('support.message')}
                    </Label>
                    <Textarea
                        id="message"
                        name="message"
                        bind:value={contactForm.message}
                        placeholder={t('support.message')}
                        class="min-h-28 rounded-[10px] border-black/20 bg-white px-5 py-3 text-xs text-black placeholder:text-black/30 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand/20"
                    />
                    <InputError message={contactForm.errors.message} />
                </div>

                <button
                    type="submit"
                    disabled={contactForm.processing}
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-full bg-brand text-sm font-medium text-white transition-colors hover:bg-brand-dark focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-60"
                >
                    {#if contactForm.processing}<Spinner />{/if}
                    {t('support.send_message')}
                </button>
            </form>
        </section>

        <div
            in:fade={{ duration: 250 }}
            class="grid grid-cols-1 gap-6 sm:grid-cols-2"
        >
            <div
                class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
            >
                <p class="mb-3 text-sm text-black">
                    {t('support.contact_info')}
                </p>
                <div class="space-y-2 text-start text-[12px] text-[#5f5f5f]">
                    <p>
                        {t('support.email_label')}
                        <span class="text-brand">Ruwad@gmail.com</span>
                    </p>
                    <p>
                        {t('support.linkedin')}
                        <span class="text-brand">Ruwad-رواد</span>
                    </p>
                    <p>
                        {t('support.twitter')}
                        <span class="text-brand">Ruwad-رواد</span>
                    </p>
                </div>
            </div>
            <div
                class="rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
            >
                <p class="mb-3 text-sm text-black">{t('support.location')}</p>
                <div
                    class="text-start text-[12px] leading-relaxed text-[#5f5f5f]"
                >
                    {t('support.address_line1')}<br />
                    {t('support.address_line2')}<br />
                    {t('support.address_line3')}
                    <br /><br />
                    {t('support.phone')}
                </div>
            </div>
        </div>

        <footer class="mt-6 text-center text-[12px] text-[#7e7e7e]">
            {t('support.footer')}
            <span class="cursor-pointer hover:text-brand">LinkedIn</span>
            |
            <span class="cursor-pointer hover:text-brand">X</span>
        </footer>
    </div>
</div>
