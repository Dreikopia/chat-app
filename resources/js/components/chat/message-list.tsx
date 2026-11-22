import { MessageBubble } from '@/components/chat/message-bubble';
import type { ChatMessage } from '@/types';

type MessageListProps = {
    messages: ChatMessage[];
    currentUserId: number;
};

export function MessageList({
    messages,
    currentUserId,
}: MessageListProps) {
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
                    currentUserId={currentUserId}
                />
            ))}
        </div>
    );
}