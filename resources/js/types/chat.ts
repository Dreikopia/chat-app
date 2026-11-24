export type ConversationPartner = {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    unread_count?: number;
    is_active?: boolean;
    last_message?: string | null;
    last_message_at?: string | null;
    last_message_is_mine?: boolean;
    last_message_is_deleted?: boolean;
    last_message_is_read?: boolean;
};

export type ChatMessage = {
    id: number;
    sender_id: number;
    recipient_id: number;
    body: string | null;
    is_deleted: boolean;
    read_at: string | null;
    created_at: string;
    updated_at: string;
};
