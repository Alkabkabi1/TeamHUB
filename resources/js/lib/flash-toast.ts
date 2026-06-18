import { router } from '@inertiajs/svelte';
import { toast } from 'svelte-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (data) {
            toast[data.type](data.message);
        }

        // A flashed download URL points at an attachment response, so following
        // it triggers a file download without navigating away from the page.
        const download = flash?.download as string | undefined;

        if (download) {
            const anchor = document.createElement('a');
            anchor.href = download;
            anchor.rel = 'noopener';
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
        }
    });
}
