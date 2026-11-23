import { Link } from '@inertiajs/react';
import { show } from '@/routes/chat';
import type { ConversationPartner } from '@/types/chat';
import { cn } from '@/lib/utils';

type Props = {
    conversation: ConversationPartner;
    isActive: boolean;
};

export function ConversationRow({ conversation, isActive }: Props) {
    return (
        <Link
            href={show(conversation.id)}
            className={cn(
                'flex items-center gap-3 rounded-md p-2 transition-colors hover:bg-sidebar-accent',
                isActive && 'bg-sidebar-accent text-sidebar-accent-foreground',
            )}
            aria-current={isActive ? 'page' : undefined}
        >
            <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-medium">
                {conversation.name.charAt(0).toUpperCase()}
            </div>

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">
                    {conversation.name}
                </p>

                <p className="truncate text-xs text-muted-foreground">
                    @{conversation.username}
                </p>
            </div>

            {!!conversation.unread_count && (
                <span
                    className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-primary-foreground"
                    aria-label={`${conversation.unread_count} unread messages`}
                >
                    {conversation.unread_count > 99
                        ? '99+'
                        : conversation.unread_count}
                </span>
            )}
        </Link>
    );
}
