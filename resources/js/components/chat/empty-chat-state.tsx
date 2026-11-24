import { MessagesSquare } from 'lucide-react';

export function EmptyChatState() {
    return (
        <div className="hidden flex-1 flex-col items-center justify-center gap-3 p-6 text-center md:flex">
            <MessagesSquare className="size-10 text-quiet/60" />

            <p className="text-sm text-quiet">
                Select a conversation to start chatting.
            </p>
        </div>
    );
}
