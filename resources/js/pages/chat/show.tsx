import { Head, usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { ChatHeader } from '@/components/chat/chat-header';
import { MessageComposer } from '@/components/chat/message-composer';
import { MessageList } from '@/components/chat/message-list';
import type { Auth, ChatMessage, ConversationPartner } from '@/types';

type Props = {
    user: ConversationPartner;
    messages: ChatMessage[];
    canMessage: boolean;
};

type RealtimeMessage = ChatMessage & { sender_name: string };

export default function ChatShow({ user, messages, canMessage }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [incoming, setIncoming] = useState<ChatMessage[]>([]);
    const [deletedIds, setDeletedIds] = useState<number[]>([]);

    useEcho<RealtimeMessage>(
        `user.${auth.user.id}`,
        '.message.sent',
        (message) => {
            if (message.sender_id !== user.id) {
                toast.info(`${message.sender_name} sent you a message`);

                return;
            }

            setIncoming((current) =>
                current.some(({ id }) => id === message.id)
                    ? current
                    : [...current, message],
            );
        },
    );

    useEcho<{ id: number }>(
        `user.${auth.user.id}`,
        '.message.deleted',
        (event) => {
            setDeletedIds((current) =>
                current.includes(event.id)
                    ? current
                    : [...current, event.id],
            );
        },
    );

    // Inertia keeps state on the redirect after sending, so merge with the fresh props.
    const liveMessages = [
        ...messages,
        ...incoming.filter(({ id }) => !messages.some((m) => m.id === id)),
    ].map((message) =>
        deletedIds.includes(message.id)
            ? {
                  ...message,
                  // Deleting hides the message from the recipient; the sender keeps it.
                  body:
                      message.sender_id === auth.user.id
                          ? message.body
                          : null,
                  is_deleted: true,
              }
            : message,
    );

    return (
        <>
            <Head title={user.name} />
            <ChatHeader partner={user} />
            <MessageList
                messages={liveMessages}
                currentUserId={auth.user.id}
            />
            {canMessage ? (
                <MessageComposer recipientId={user.id} />
            ) : (
                <p className="border-t p-4 text-sm text-muted-foreground">
                    You're no longer friends with {user.name}. You can still
                    read your conversation, but you can't send new messages.
                </p>
            )}
        </>
    );
}
