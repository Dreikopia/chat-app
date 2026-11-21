import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (data) {
                toast[data.type](data.message);

                return;
            }

            if (typeof flash?.success === 'string') {
                toast.success(flash.success);
            }

            if (typeof flash?.error === 'string') {
                toast.error(flash.error);
            }
        });
    }, []);
}
