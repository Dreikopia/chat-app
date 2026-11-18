import { Head } from '@inertiajs/react';
import { ChatHeader } from '@/components/chat/chat-header';
import { MessageList } from '@/components/chat/message-list';
import type { ChatMessage, ConversationPartner } from '@/types/chat';
import { MessageComposer } from '@/components/chat/message-composer';

type Props = {
    user: ConversationPartner;
    messages: ChatMessage[];
};

export default function ChatShow({ user, messages }: Props) {
    return (
        <>
            <Head title={user.name} />
            <ChatHeader partner={user} />
            <MessageList messages={messages} />
            <MessageComposer recipientId={user.id} />
        </>
    );
}
