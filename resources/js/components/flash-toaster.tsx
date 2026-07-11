import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

type FlashPayload = { success?: string | null; error?: string | null };

// Listens to Inertia navigation events (context-free, so it can live outside the
// <App> tree) and shows a toast for any flash message on the resolved page.
export function FlashToaster() {
    useEffect(() => {
        return router.on('success', (event) => {
            const detail = event.detail as {
                page?: { props?: { flash?: FlashPayload } };
            };
            const flash = detail.page?.props?.flash;

            if (flash?.success) {
                toast.success(flash.success);
            }

            if (flash?.error) {
                toast.error(flash.error);
            }
        });
    }, []);

    return null;
}
