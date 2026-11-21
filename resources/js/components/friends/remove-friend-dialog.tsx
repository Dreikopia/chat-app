import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { MouseEvent } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { destroy } from '@/routes/friends';
import type { User } from '@/types/friendship';

type Props = {
    friend: User;
};

export default function RemoveFriendDialog({ friend }: Props) {
    const [open, setOpen] = useState(false);
    const [isRemoving, setIsRemoving] = useState(false);

    const handleRemove = (event: MouseEvent<HTMLButtonElement>) => {
        // Keep the dialog open until the request finishes so we can show pending state.
        event.preventDefault();
        setIsRemoving(true);

        router.delete(destroy.url(friend.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
            onFinish: () => setIsRemoving(false),
        });
    };

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="text-muted-foreground hover:text-destructive"
                >
                    Remove
                </Button>
            </AlertDialogTrigger>

            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Remove {friend.name}?</AlertDialogTitle>

                    <AlertDialogDescription>
                        They will be removed from your friends list. You can
                        send them a new friend request later.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>

                    <AlertDialogAction
                        disabled={isRemoving}
                        onClick={handleRemove}
                    >
                        {isRemoving ? 'Removing...' : 'Remove friend'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
