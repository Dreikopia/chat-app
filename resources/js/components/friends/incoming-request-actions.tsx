import { router } from '@inertiajs/react';
import { useState } from 'react';
import FriendRequestController from '@/actions/App/Http/Controllers/FriendRequestController';
import { Button } from '@/components/ui/button';
import type { Friendship } from '@/types/friendship';

type Props = {
    request: Friendship;
};

export default function IncomingRequestActions({ request }: Props) {
    const [pendingAction, setPendingAction] = useState<
        'accept' | 'decline' | null
    >(null);

    const isPending = pendingAction !== null;

    const accept = () => {
        setPendingAction('accept');

        router.patch(
            FriendRequestController.accept.url(request.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setPendingAction(null),
            },
        );
    };

    const decline = () => {
        setPendingAction('decline');

        router.delete(FriendRequestController.decline.url(request.id), {
            preserveScroll: true,
            onFinish: () => setPendingAction(null),
        });
    };

    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={isPending}
                onClick={accept}
            >
                {pendingAction === 'accept' ? 'Accepting...' : 'Accept'}
            </Button>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                className="text-muted-foreground"
                disabled={isPending}
                onClick={decline}
            >
                {pendingAction === 'decline' ? 'Declining...' : 'Decline'}
            </Button>
        </>
    );
}
