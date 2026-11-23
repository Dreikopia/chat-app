import { cn } from '@/lib/utils';
import type { ChatMessage } from '@/types/chat';
import { router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '../ui/alert-dialog';
import { Button } from '../ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';

type Props = {
    message: ChatMessage;
    currentUserId: number;
};

export function MessageBubble({ message, currentUserId }: Props) {
    const isOwn = message.sender_id === currentUserId;

    const sentAt = new Date(message.created_at);

    const sentAtTime = new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    }).format(sentAt);

    const sentAtFull = new Intl.DateTimeFormat(undefined, {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(sentAt);

    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        setDeleteError(false);

        router.delete(`/messages/${message.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
            },
            onError: () => {
                setDeleteError(true);
            },
            onFinish: () => {
                setDeleting(false);
            },
        });
    };

    const optionsMenu = !message.is_deleted && (
        <div className="shrink-0 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 rounded-full text-muted-foreground hover:text-foreground"
                        aria-label="Message options"
                    >
                        <MoreVertical className="size-4" />
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align={isOwn ? 'end' : 'start'}>
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => setDialogOpen(true)}
                    >
                        Delete message
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );

    return (
        <div className={cn('flex', isOwn ? 'justify-end' : 'justify-start')}>
            <div className="group flex max-w-[75%] items-start gap-1">
                {isOwn && optionsMenu}

                <div
                    className={cn(
                        'flex min-w-0 flex-col gap-1',
                        isOwn ? 'items-end' : 'items-start',
                    )}
                >
                    <div
                        className={cn(
                            'rounded-2xl px-3 py-2 text-sm break-words whitespace-pre-wrap',
                            isOwn
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-foreground',
                        )}
                    >
                        {message.body === null ? (
                            <p
                                className={cn(
                                    'italic',
                                    isOwn
                                        ? 'text-primary-foreground/70'
                                        : 'text-muted-foreground',
                                )}
                            >
                                This message was deleted
                            </p>
                        ) : (
                            <p>{message.body}</p>
                        )}
                    </div>

                    <time
                        dateTime={message.created_at}
                        title={sentAtFull}
                        className="px-1 text-[11px] text-muted-foreground"
                    >
                        {sentAtTime}
                    </time>
                </div>

                {!isOwn && optionsMenu}
            </div>

            <AlertDialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete message</AlertDialogTitle>
                        <AlertDialogDescription>
                            {isOwn
                                ? "This message will be hidden from the other person. You'll still see it."
                                : 'This message will be removed from your view. The other person will still see it.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    {deleteError && (
                        <p className="text-sm text-destructive" role="alert">
                            Couldn't delete the message. Try again.
                        </p>
                    )}

                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={deleting}>
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={(event) => {
                                event.preventDefault();
                                handleDelete();
                            }}
                            disabled={deleting}
                        >
                            {deleting ? 'Deleting...' : 'Delete'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
