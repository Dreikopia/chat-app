import { useForm } from "@inertiajs/react";
import React from "react";
import { Textarea } from "../ui/textarea";
import { Button } from "../ui/button";


type Props = {
    recipientId: number;
};

export function MessageComposer({ recipientId }: Props) {
    const form = useForm({
        body: '',
    });

    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();

        form.post(`/chat/${recipientId}/messages`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <form onSubmit={submit} className="flex gap-2 border-t p-4">
            <Textarea
                value={form.data.body}
                onChange={(e) => form.setData('body', e.target.value)}
                placeholder="Write a message..."
                rows={2}
                disabled={form.processing}
            />

            <Button
                type="submit"
                disabled={form.processing || !form.data.body.trim()}
            >
                Send
            </Button>
        </form>
    );
}