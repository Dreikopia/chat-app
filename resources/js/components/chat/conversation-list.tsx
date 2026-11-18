import { ConversationRow } from '@/components/chat/conversation-row';
import type { ConversationPartner } from '@/types/chat';

type Props = {
    conversations: ConversationPartner[];
    activeId: number | null;
};

export function ConversationList({ conversations, activeId }: Props) {
    if (conversations.length === 0) {
        return (
            <p className="px-3 py-4 text-sm text-muted-foreground">
                No chats yet. Search for someone to start.
            </p>
        );
    }

    return (
        <nav className="flex flex-col gap-0.5 p-2" aria-label="Conversations">
            {conversations.map((conversation) => (
                <ConversationRow
                    key={conversation.id}
                    conversation={conversation}
                    isActive={conversation.id === activeId}
                />
            ))}
        </nav>
    );
}
