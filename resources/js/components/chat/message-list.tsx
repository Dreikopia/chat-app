import { Fragment, useEffect, useRef } from 'react';
import { MessageBubble } from '@/components/chat/message-bubble';
import type { ChatMessage } from '@/types';

type MessageListProps = {
    messages: ChatMessage[];
    currentUserId: number;
};

function startOfDay(date: Date): number {
    return new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
    ).getTime();
}

function dayLabel(date: Date): string {
    const daysAgo = Math.round(
        (startOfDay(new Date()) - startOfDay(date)) / 86_400_000,
    );

    if (daysAgo === 0) {
        return 'Today';
    }

    if (daysAgo === 1) {
        return 'Yesterday';
    }

    if (daysAgo > 1 && daysAgo < 7) {
        return date.toLocaleDateString(undefined, { weekday: 'long' });
    }

    return date.toLocaleDateString(undefined, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export function MessageList({ messages, currentUserId }: MessageListProps) {
    const listRef = useRef<HTMLDivElement>(null);
    const lastMessageId = messages[messages.length - 1]?.id;

    useEffect(() => {
        const list = listRef.current;

        if (list) {
            list.scrollTop = list.scrollHeight;
        }
    }, [lastMessageId]);

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
        <div
            ref={listRef}
            className="flex flex-1 flex-col gap-2 overflow-y-auto p-4"
        >
            {messages.map((message, index) => {
                const sentAt = new Date(message.created_at);
                const previousSentAt =
                    index > 0 ? new Date(messages[index - 1].created_at) : null;
                const startsNewDay =
                    previousSentAt === null ||
                    startOfDay(previousSentAt) !== startOfDay(sentAt);

                return (
                    <Fragment key={message.id}>
                        {startsNewDay && (
                            <div className="flex justify-center py-2">
                                <time
                                    dateTime={message.created_at}
                                    className="text-[11px] font-medium text-muted-foreground"
                                >
                                    {dayLabel(sentAt)}
                                </time>
                            </div>
                        )}

                        <MessageBubble
                            message={message}
                            currentUserId={currentUserId}
                        />
                    </Fragment>
                );
            })}
        </div>
    );
}
