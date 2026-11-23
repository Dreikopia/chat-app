import { useForm } from '@inertiajs/react';
import { type FormEvent, useEffect, useState } from 'react';
import AvatarController from '@/actions/App/Http/Controllers/Settings/AvatarController';
import FriendAvatar from '@/components/friends/friend-avatar';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { User } from '@/types';

function describeWait(seconds: number): string {
    return seconds >= 60
        ? `${Math.ceil(seconds / 60)} minutes`
        : `${seconds} seconds`;
}

export default function AvatarForm({ user }: { user: User }) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        avatar: File | null;
    }>({
        avatar: null,
    });

    const [preview, setPreview] = useState<string | null>(null);
    const [secondsToWait, setSecondsToWait] = useState(0);

    useEffect(() => {
        if (!data.avatar) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(data.avatar);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [data.avatar]);

    useEffect(() => {
        if (secondsToWait === 0) {
            return;
        }

        const timer = window.setInterval(() => {
            setSecondsToWait((seconds) => Math.max(0, seconds - 1));
        }, 1000);

        return () => window.clearInterval(timer);
    }, [secondsToWait]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post(AvatarController.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset('avatar');
                setPreview(null);
            },
            onHttpException: (response) => {
                if (response.status !== 429) {
                    return;
                }

                setSecondsToWait(Number(response.headers['retry-after'] ?? 60));

                return false;
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <Label htmlFor="avatar">Photo</Label>

            <div className="flex items-center gap-4">
                <FriendAvatar
                    name={user.name}
                    avatar={preview ?? user.avatar}
                    className="size-24 text-2xl"
                />

                <Button asChild variant="outline">
                    <label htmlFor="avatar">Choose image</label>
                </Button>

                <input
                    id="avatar"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="sr-only"
                    onChange={(event) =>
                        setData('avatar', event.target.files?.[0] ?? null)
                    }
                />
            </div>

            <InputError message={errors.avatar} />

            {secondsToWait > 0 && (
                <p className="text-sm leading-5 text-destructive" role="alert">
                    Too many photo uploads. Try again in{' '}
                    {describeWait(secondsToWait)}.
                </p>
            )}

            <Button
                type="submit"
                disabled={processing || !data.avatar || secondsToWait > 0}
                data-test="update-avatar-button"
            >
                Save photo
            </Button>
        </form>
    );
}
