import { router } from '@inertiajs/react';
import { useState } from 'react';
import FriendRequestController from '@/actions/App/Http/Controllers/FriendRequestController';
import { Button } from '@/components/ui/button';
import type { Friendship } from '@/types/friendship';

type Props = {
    request: Friendship;
};

export default function OutgoingRequestActions({ request }: Props) {
    const [isCancelling, setIsCancelling] = useState(false);

    const cancel = () => {
        setIsCancelling(true);

        router.delete(FriendRequestController.cancel.url(request.id), {
            preserveScroll: true,
            onFinish: () => setIsCancelling(false),
        });
    };

    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className="text-muted-foreground"
            disabled={isCancelling}
            onClick={cancel}
        >
            {isCancelling ? 'Cancelling...' : 'Cancel request'}
        </Button>
    );
}
