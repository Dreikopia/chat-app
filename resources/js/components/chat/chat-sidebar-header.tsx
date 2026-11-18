import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Plus } from 'lucide-react';

export function ChatSidebarHeader() {
    return (
        <div className="flex items-center gap-2 p-2">
            <SidebarTrigger className="shrink-0 md:hidden" />

            <h1 className="min-w-0 flex-1 truncate text-lg font-semibold">
                Chats
            </h1>

            <Button variant="ghost" size="icon" title="New chat">
                <Plus />
                <span className="sr-only">New chat</span>
            </Button>
        </div>
    );
}
