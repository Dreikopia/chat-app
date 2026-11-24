import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import ActiveStatus from '@/components/active-status';
import FriendAvatar from '@/components/friends/friend-avatar';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { index as chatIndex } from '@/routes/chat';
import type { ConversationPartner } from '@/types/chat';

export function ChatHeader({ partner }: { partner: ConversationPartner }) {
    return (
        <header className="flex h-16 shrink-0 items-center gap-3 border-b px-4">
            <SidebarTrigger className="hidden md:flex" />

            <Button
                variant="ghost"
                size="icon"
                asChild
                className="-ml-2 md:hidden"
            >
                <Link href={chatIndex()} aria-label="Back to chats">
                    <ChevronLeft className="size-5" />
                </Link>
            </Button>

            <FriendAvatar
                name={partner.name}
                avatar={partner.avatar}
                className="size-8 text-xs"
            />

            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <p className="min-w-0 flex-1 truncate font-medium">
                        {partner.name}
                    </p>

                    <ActiveStatus active={partner.is_active} />
                </div>

                <p className="truncate text-xs text-muted-foreground">
                    @{partner.username}
                </p>
            </div>
        </header>
    );
}
