import { ConversationList } from '@/components/chat/conversation-list';
import { ChatSidebarHeader } from '@/components/chat/chat-sidebar-header';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
} from '@/components/ui/sidebar';
import type { ConversationPartner } from '@/types/chat';

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
        </Sidebar>
    );
}
