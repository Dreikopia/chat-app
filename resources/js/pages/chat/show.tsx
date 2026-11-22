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
};

type RealtimeMessage = ChatMessage & { sender_name: string };

export default function ChatShow({ user, messages }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [incoming, setIncoming] = useState<ChatMessage[]>([]);

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

    // Inertia keeps state on the redirect after sending, so merge with the fresh props.
    const liveMessages = [
        ...messages,
        ...incoming.filter(({ id }) => !messages.some((m) => m.id === id)),
    ];

    return (
        <>
            <Head title={user.name} />
            <ChatHeader partner={user} />
            <MessageList messages={liveMessages} />
            <MessageComposer recipientId={user.id} />
        </>
    );
}
