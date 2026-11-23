import FriendAvatar from '@/components/friends/friend-avatar';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { ConversationPartner } from '@/types/chat';

export function ChatHeader({ partner }: { partner: ConversationPartner }) {
    return (
        <header className="flex h-16 shrink-0 items-center gap-3 border-b px-4">
            <SidebarTrigger className="md:hidden" />

            <FriendAvatar
                name={partner.name}
                avatar={partner.avatar}
                className="size-8 text-xs"
            />

            <div className="min-w-0">
                <p className="truncate font-medium">{partner.name}</p>
                <p className="truncate text-xs text-muted-foreground">
                    @{partner.username}
                </p>
            </div>
        </header>
    );
}
