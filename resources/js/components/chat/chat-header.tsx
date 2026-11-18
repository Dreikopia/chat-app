import { SidebarTrigger } from '@/components/ui/sidebar';
import type { ConversationPartner } from '@/types/chat';

export function ChatHeader({ partner }: { partner: ConversationPartner }) {
    return (
        <header className="flex h-16 shrink-0 items-center gap-3 border-b px-4">
            <SidebarTrigger className="md:hidden" />

            <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-medium">
                {partner.name.charAt(0).toUpperCase()}
            </div>

            <div className="min-w-0">
                <p className="truncate font-medium">{partner.name}</p>
                <p className="truncate text-xs text-muted-foreground">
                    @{partner.username}
                </p>
            </div>
        </header>
    );
}
