import { cn } from '@/lib/utils';
import type { ChatMessage } from '@/types/chat';

type Props = {
    message: ChatMessage;
    isOwn: boolean;
};

export function MessageBubble({ message, isOwn }: Props) {
    return (
        <div className={cn('flex', isOwn ? 'justify-end' : 'justify-start')}>
            <div
                className={cn(
                    'max-w-[75%] rounded-2xl px-3 py-2 text-sm break-words',
                    isOwn
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-foreground',
                )}
            >
                {message.body}
            </div>
        </div>
    );
}
