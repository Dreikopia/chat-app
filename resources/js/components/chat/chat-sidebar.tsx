import { ConversationList } from '@/components/chat/conversation-list';
import { ChatSidebarHeader } from '@/components/chat/chat-sidebar-header';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import type { ConversationPartner } from '@/types/chat';
import { NavUser } from '../nav-user';

type Props = {
    conversations: ConversationPartner[];
    activeId: number | null;
};

export function ChatSidebar({ conversations, activeId }: Props) {
    return (
        <Sidebar collapsible="offcanvas" variant="inset">
            <SidebarHeader>
                <ChatSidebarHeader />
            </SidebarHeader>

            <SidebarContent>
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
