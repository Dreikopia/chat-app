import { SidebarTrigger } from '@/components/ui/sidebar';
import { MessagesSquare } from 'lucide-react';

export function EmptyChatState() {
    return (
        <>
            <header className="flex h-16 shrink-0 items-center gap-2 border-b px-4 md:hidden">
                <SidebarTrigger />
                <span className="font-medium">Chats</span>
            </header>

            <div className="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center">
                <MessagesSquare className="size-10 text-muted-foreground/60" />

                <p className="text-sm text-muted-foreground">
                    Select a conversation to start chatting.
                </p>
            </div>
        </>
    );
}
