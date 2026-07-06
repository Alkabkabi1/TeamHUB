import { createInertiaApp } from '@inertiajs/svelte';
import { router } from '@inertiajs/svelte';
import type { Component } from 'svelte';
import AppShellLayout from '@/layouts/AppShellLayout.svelte';
import GuestLayout from '@/layouts/GuestLayout.svelte';
import SettingsLayout from '@/layouts/settings/Layout.svelte';
import { initializeBrandTheme } from '@/lib/brand-theme';
import { initializeFlashToast } from '@/lib/flash-toast';
import { syncDocumentLocale } from '@/lib/i18n.svelte';
import { initializeTheme } from '@/lib/theme.svelte';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob<{ default: Component }>(
            './pages/**/*.svelte',
        );
        const page = pages[`./pages/${name}.svelte`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        return page();
    },
    defaults: {
        visitOptions: (_href, options) => {
            const method = (options.method ?? 'get').toString().toLowerCase();

            return {
                ...options,
                viewTransition: method === 'get' && !prefersReducedMotion(),
            };
        },
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'Support':
                return GuestLayout;
            case name === 'app/Entry':
            case name === 'WorkspaceMembershipRequestForm':
            case name === 'ErrorPage':
            case name.startsWith('auth/'):
                return null;
            case name.startsWith('settings/'):
                return [AppShellLayout, SettingsLayout];
            default:
                return AppShellLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
initializeBrandTheme();
initializeFlashToast();

router.on('navigate', () => {
    syncDocumentLocale();
});

if (typeof document !== 'undefined') {
    syncDocumentLocale();
}
