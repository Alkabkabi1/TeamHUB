import { createInertiaApp } from '@inertiajs/svelte';
import { router } from '@inertiajs/svelte';
import AppLayout from '@/layouts/AppLayout.svelte';
import MainLayout from '@/layouts/MainLayout.svelte';
import SettingsLayout from '@/layouts/settings/Layout.svelte';
import TeamHubAppLayout from '@/layouts/TeamHubAppLayout.svelte';
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
    defaults: {
        // Animate page-to-page navigations with the View Transitions API.
        // Limited to GET visits (real navigations) and disabled when the user
        // prefers reduced motion, so mutations and a11y preferences are honored.
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
            // Public/site pages share the persistent MainLayout (header,
            // sidebar, search). Applied centrally so pages render only their
            // own content and the chrome never remounts between navigations.
            case name === 'Welcome':
            case name === 'StudentDashboard':
            case name === 'clubs/Manage':
            case name === 'clubs/certificate-templates/Index':
            case name === 'clubs/certificate-templates/Editor':
            case name === 'AdminDashboard':
            case name === 'ClubPage':
            case name === 'ClubsPage':
            case name === 'committees/Index':
            case name === 'committees/Manage':
            case name === 'committees/Form':
            case name === 'CommitteePage':
            case name === 'EventsPage':
            case name === 'EventDetailPage':
            case name === 'EventForm':
            case name === 'events/Scan':
            case name === 'ResourcesPage':
            case name === 'NewsPage':
            case name === 'NewsArticle':
            case name === 'NewsForm':
            case name === 'ClubTheme':
            case name === 'Support':
                return MainLayout;
            case name === 'MyTasks':
            case name === 'Notifications':
            case name === 'clubs/Members':
            case name === 'committees/Files':
            case name === 'committees/Updates':
            case name.startsWith('committees/tasks/'):
                return TeamHubAppLayout;
            // ClubJoinForm and auth pages are intentionally full-bleed: they
            // wrap themselves (auth needs a per-page title) so they get no
            // central layout here.
            case name === 'ClubJoinForm':
            case name.startsWith('team-hub/'):
            case name === 'ErrorPage':
            case name.startsWith('auth/'):
                return null;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return TeamHubAppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Keep the brand color in sync with the shared theme prop (university default,
// overridable per club)...
initializeBrandTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

router.on('navigate', () => {
    syncDocumentLocale();
});

if (typeof document !== 'undefined') {
    syncDocumentLocale();
}
