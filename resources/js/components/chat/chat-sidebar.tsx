import { Link, usePage } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { ChatSidebarHeader } from '@/components/chat/chat-sidebar-header';
import { ConversationList } from '@/components/chat/conversation-list';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { index as friends } from '@/routes/friends';
import type { ConversationPartner } from '@/types/chat';
import { NavUser } from '../nav-user';

type Props = {
    conversations: ConversationPartner[];
    activeId: number | null;
};

export function ChatSidebar({ conversations, activeId }: Props) {
    const { isCurrentUrl } = useCurrentUrl();
    const { pendingFriendRequestsCount } = usePage().props;

    return (
        <Sidebar collapsible="offcanvas" variant="inset">
            <SidebarHeader>
                <ChatSidebarHeader />
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                asChild
                                isActive={isCurrentUrl(friends())}
                            >
                                <Link href={friends()} prefetch>
                                    <Users />
                                    <span>Friends</span>
                                </Link>
                            </SidebarMenuButton>

                            {pendingFriendRequestsCount > 0 && (
                                <SidebarMenuBadge>
                                    {pendingFriendRequestsCount > 99
                                        ? '99+'
                                        : pendingFriendRequestsCount}
                                </SidebarMenuBadge>
                            )}
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>

                <ConversationList
                    conversations={conversations}
                    activeId={activeId}
                />
            </SidebarContent>
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
