export type ConversationPartner = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
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
