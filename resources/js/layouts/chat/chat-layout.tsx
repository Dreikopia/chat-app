import type { ReactNode } from 'react';
import { ChatSidebar } from '@/components/chat/chat-sidebar';
import { MobileTabBar } from '@/components/mobile-tab-bar';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import type { ConversationPartner } from '@/types/chat';

type Props = {
    children: ReactNode;
    conversations?: ConversationPartner[];
    activeId?: number | null;
};

export default function ChatLayout({
    children,
    conversations = [],
    activeId = null,
}: Props) {
    return (
        <SidebarProvider className="h-svh overflow-hidden">
            <ChatSidebar conversations={conversations} activeId={activeId} />

            <SidebarInset className="min-w-0 overflow-hidden">
                {children}
                <MobileTabBar />
            </SidebarInset>
        </SidebarProvider>
    );
}
