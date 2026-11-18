import { Head } from '@inertiajs/react';
import { EmptyChatState } from '@/components/chat/empty-chat-state';

export default function ChatIndex() {
    return (
        <>
            <Head title="Chats" />
            <EmptyChatState />
        </>
    );
}
