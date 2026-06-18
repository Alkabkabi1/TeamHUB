<script lang="ts">
    import {
        StarsIcon,
        TextFontIcon,
        UniversalAccessCircleIcon,
        VolumeHighIcon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { router } from '@inertiajs/svelte';
    import { page } from '@inertiajs/svelte';
    import AssistantPanel from '@/components/assistant/AssistantPanel.svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { openAssistant } from '@/lib/assistant.svelte';
    import { fontSizeState } from '@/lib/font-size.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { screenReaderState } from '@/lib/screen-reader.svelte';

    const screenReader = screenReaderState();
    const fontSize = fontSizeState();
    const volumeHighFilledIcon: IconSvgElement = [
        [
            'path',
            {
                d: 'M14 14.8135V9.18646C14 6.04126 14 4.46866 13.0747 4.0773C12.1494 3.68593 11.0603 4.79793 8.88232 7.02192C7.75439 8.17365 7.11085 8.42869 5.50604 8.42869C4.10257 8.42869 3.40084 8.42869 2.89675 8.77262C1.85035 9.48655 2.00852 10.882 2.00852 12C2.00852 13.118 1.85035 14.5134 2.89675 15.2274C3.40084 15.5713 4.10257 15.5713 5.50604 15.5713C7.11085 15.5713 7.75439 15.8264 8.88232 16.9781C11.0603 19.2021 12.1494 20.3141 13.0747 19.9227C14 19.5313 14 17.9587 14 14.8135Z',
                fill: 'currentColor',
                stroke: 'currentColor',
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
                strokeWidth: '1.5',
                key: '0',
            },
        ],
        [
            'path',
            {
                d: 'M17 9C17.6254 9.81968 18 10.8634 18 12C18 13.1366 17.6254 14.1803 17 15',
                stroke: 'currentColor',
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
                strokeWidth: '1.5',
                key: '1',
            },
        ],
        [
            'path',
            {
                d: 'M20 7C21.2508 8.36613 22 10.1057 22 12C22 13.8943 21.2508 15.6339 20 17',
                stroke: 'currentColor',
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
                strokeWidth: '1.5',
                key: '2',
            },
        ],
    ];
    const locale = $derived((page.props.locale as string) ?? 'ar');
    const accessibilityLabel = $derived(
        locale === 'ar' ? 'خيارات الوصول' : 'Accessibility options',
    );
    const screenReaderLabel = $derived(
        locale === 'ar' ? 'قارئ الشاشة' : 'Screen Reader Support',
    );
    const fontSizeLabel = $derived(
        locale === 'ar' ? 'حجم الخط' : 'Font Size Controls',
    );
    const menuDirection = $derived(locale === 'ar' ? 'rtl' : 'ltr');
    const menuAlign = $derived(locale === 'ar' ? 'end' : 'start');
    const menuItemClass = $derived(
        locale === 'ar'
            ? 'w-full justify-end gap-2 px-2.5 py-2 text-right [&_span]:order-1 [&_svg]:order-2'
            : 'w-full justify-start gap-2 px-2.5 py-2 text-left',
    );
    const screenReaderIcon = $derived(
        screenReader.enabled ? volumeHighFilledIcon : VolumeHighIcon,
    );
    let showFontSizeControls = $state(false);

    function switchLocale(next: 'ar' | 'en'): void {
        if (next === locale) {
            return;
        }

        router.post(
            '/locale',
            { locale: next },
            { preserveScroll: true, preserveState: false },
        );
    }

    function toggleScreenReader(event: MouseEvent): void {
        event.stopPropagation();
        screenReader.toggle();
    }

    function toggleFontSizeControls(event: MouseEvent): void {
        event.stopPropagation();
        showFontSizeControls = !showFontSizeControls;
    }

    function updateFontSize(event: Event): void {
        event.stopPropagation();

        if (!(event.currentTarget instanceof HTMLInputElement)) {
            return;
        }

        fontSize.setIndex(Number(event.currentTarget.value));
    }

    function decreaseFontSize(event: MouseEvent): void {
        event.stopPropagation();
        fontSize.setIndex(fontSize.index - 1);
    }

    function increaseFontSize(event: MouseEvent): void {
        event.stopPropagation();
        fontSize.setIndex(fontSize.index + 1);
    }

    $effect(() => {
        screenReader.initialize();
        fontSize.initialize();
    });
</script>

<div
    class="fixed bottom-4 start-4 z-50 flex items-center gap-2"
    aria-label={accessibilityLabel}
>
    <!-- AI assistant launcher (open to guests and members), beside the accessibility icon -->
    <button
        type="button"
        onclick={openAssistant}
        aria-label={t('assistant.open')}
        class="flex size-12 cursor-pointer items-center justify-center rounded-full border border-black/10 bg-white text-brand shadow-[0_4px_20px_rgba(0,0,0,0.12)] transition-colors hover:bg-brand hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
    >
        <HugeiconsIcon icon={StarsIcon} size={24} strokeWidth={1.8} />
    </button>
    <DropdownMenu>
        <DropdownMenuTrigger>
            {#snippet child({ props })}
                <button
                    type="button"
                    class="flex size-9 cursor-pointer items-center justify-center rounded-full border border-black/10 bg-white text-brand shadow-[0_4px_20px_rgba(0,0,0,0.12)] transition-colors hover:bg-brand hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
                    aria-label={accessibilityLabel}
                    {...props}
                >
                    <HugeiconsIcon
                        icon={UniversalAccessCircleIcon}
                        size={20}
                        strokeWidth={1.8}
                    />
                </button>
            {/snippet}
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align={menuAlign}
            side="top"
            sideOffset={8}
            dir={menuDirection}
            class="w-[224px] p-[6px]"
        >
            <DropdownMenuItem
                class={menuItemClass}
                onclick={toggleScreenReader}
                aria-pressed={screenReader.enabled}
                data-active={screenReader.enabled}
            >
                <HugeiconsIcon
                    icon={screenReaderIcon}
                    size={18}
                    strokeWidth={1.8}
                    class="text-brand"
                />
                <span>{screenReaderLabel}</span>
            </DropdownMenuItem>
            <button
                type="button"
                class="focus:bg-accent focus:text-accent-foreground not-data-[variant=destructive]:focus:**:text-accent-foreground relative flex cursor-default select-none items-center rounded-md text-sm outline-hidden [&_svg]:pointer-events-none [&_svg]:shrink-0 {menuItemClass}"
                onclick={toggleFontSizeControls}
                aria-expanded={showFontSizeControls}
            >
                <HugeiconsIcon
                    icon={TextFontIcon}
                    size={18}
                    strokeWidth={1.8}
                    class="text-brand"
                />
                <span>{fontSizeLabel}</span>
            </button>
            {#if showFontSizeControls}
                <div class="px-[10px] pb-[8px] pt-[4px]" dir={menuDirection}>
                    <div class="flex items-center gap-[8px]" dir="ltr">
                        <button
                            type="button"
                            class="flex size-[20px] cursor-pointer items-center justify-center text-[16px] font-semibold leading-none text-brand transition-colors hover:text-brand/75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30"
                            onclick={decreaseFontSize}
                            aria-label={fontSizeLabel}
                        >
                            −
                        </button>
                        <input
                            type="range"
                            min="0"
                            max="3"
                            step="1"
                            value={fontSize.index}
                            aria-label={fontSizeLabel}
                            onclick={(event) => event.stopPropagation()}
                            onchange={updateFontSize}
                            class="h-[6px] flex-1 cursor-pointer accent-brand"
                        />
                        <button
                            type="button"
                            class="flex size-[20px] cursor-pointer items-center justify-center text-[16px] font-semibold leading-none text-brand transition-colors hover:text-brand/75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30"
                            onclick={increaseFontSize}
                            aria-label={fontSizeLabel}
                        >
                            +
                        </button>
                    </div>
                    <div class="mt-[8px] flex h-[20px] items-start gap-[8px]">
                        <span
                            class="size-[20px] text-center text-[11px] font-medium text-black/50"
                        >
                            {fontSize.label}
                        </span>
                        <div
                            class="relative h-full flex-1 text-[11px] text-black/50"
                        >
                            <div
                                class="absolute left-1/3 top-0 h-2 border-s border-brand"
                                aria-hidden="true"
                            ></div>
                            <span
                                class="absolute left-1/3 top-2 -translate-x-1/2 font-medium text-black/50"
                            >
                                {locale === 'ar' ? 'افتراضي' : 'Default'}
                            </span>
                        </div>
                        <span class="size-[20px]" aria-hidden="true"></span>
                    </div>
                </div>
            {/if}

            <!-- Language switch, grouped within the accessibility options -->
            <div
                class="mt-[6px] border-t border-black/5 pt-[8px]"
                dir={menuDirection}
            >
                <p class="px-2.5 pb-1.5 text-[11px] font-medium text-black/50">
                    {t('app.switch_language')}
                </p>
                <div
                    class="mx-1 flex items-center gap-0.5 rounded-full bg-black/[0.04] p-1"
                    role="group"
                    aria-label={t('app.switch_language')}
                >
                    <button
                        type="button"
                        class="flex-1 cursor-pointer rounded-full px-3 py-1.5 text-xs font-medium transition-colors {locale ===
                        'ar'
                            ? 'bg-brand text-white'
                            : 'text-black/70 hover:bg-black/5'}"
                        onclick={() => switchLocale('ar')}
                    >
                        {t('app.locale_ar')}
                    </button>
                    <button
                        type="button"
                        class="flex-1 cursor-pointer rounded-full px-3 py-1.5 text-xs font-medium transition-colors {locale ===
                        'en'
                            ? 'bg-brand text-white'
                            : 'text-black/70 hover:bg-black/5'}"
                        onclick={() => switchLocale('en')}
                    >
                        {t('app.locale_en')}
                    </button>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</div>

<!-- The chat panel travels with the dock so the launcher works in every layout -->
<AssistantPanel />
