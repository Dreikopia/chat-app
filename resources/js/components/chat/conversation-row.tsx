import { Link } from '@inertiajs/react';
import ActiveStatus from '@/components/active-status';
import FriendAvatar from '@/components/friends/friend-avatar';
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
            <span className="relative shrink-0">
                <FriendAvatar
                    name={conversation.name}
                    avatar={conversation.avatar}
                />

                <ActiveStatus
                    active={conversation.is_active}
                    className="pointer-events-none absolute right-0 bottom-0 size-2.5 ring-2 ring-sidebar"
                />
            </span>

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
