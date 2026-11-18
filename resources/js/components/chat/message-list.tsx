import { usePage } from '@inertiajs/react';
import { MessageBubble } from '@/components/chat/message-bubble';
import type { Auth, ChatMessage } from '@/types';

export function MessageList({ messages }: { messages: ChatMessage[] }) {
    const { auth } = usePage<{ auth: Auth }>().props;

    if (messages.length === 0) {
        return (
            <div className="flex flex-1 items-center justify-center p-6">
                <p className="text-sm text-muted-foreground">
                    No messages yet. Say hello.
                </p>
            </div>
        );
    }

    return (
        <div className="flex flex-1 flex-col gap-2 overflow-y-auto p-4">
            {messages.map((message) => (
                <MessageBubble
                    key={message.id}
                    message={message}
                    isOwn={message.sender_id === auth.user.id}
                />
            ))}
        </div>
    );
}
