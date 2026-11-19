import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { ConversationRow } from '@/components/chat/conversation-row';
import type { ConversationPartner } from '@/types/chat';

type Props = {
    conversations: ConversationPartner[];
    activeId: number | null;
};

export function ConversationList({ conversations, activeId }: Props) {
    const [search, setSearch] = useState('');

    const filteredConversations = conversations.filter((conversation) => {
        const query = search.toLowerCase();

        return (
            conversation.name.toLowerCase().includes(query) ||
            conversation.username.toLowerCase().includes(query)
        );
    });

    if (conversations.length === 0) {
        return (
            <p className="px-3 py-4 text-sm text-muted-foreground">
                No chats yet. Search for someone to start.
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-2 p-2">
            <Input
                placeholder="Search..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
            />

            {filteredConversations.length > 0 ? (
                <nav className="flex flex-col gap-0.5" aria-label="Conversations">
                    {filteredConversations.map((conversation) => (
                        <ConversationRow
                            key={conversation.id}
                            conversation={conversation}
                            isActive={conversation.id === activeId}
                        />
                    ))}
                </nav>
            ) : (
                <p className="px-3 py-4 text-sm text-muted-foreground">
                    No conversations found.
                </p>
            )}
        </div>
    );
}