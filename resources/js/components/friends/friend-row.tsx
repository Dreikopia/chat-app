import type { ReactNode } from 'react';
import FriendAvatar from '@/components/friends/friend-avatar';
import type { User } from '@/types/friendship';

type Props = {
    friend: User;
    children?: ReactNode;
};

export default function FriendRow({ friend, children }: Props) {
    return (
        <div className="flex items-center gap-3 py-2.5">
            <FriendAvatar name={friend.name} />

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{friend.name}</p>

                <p className="truncate text-sm text-muted-foreground">
                    @{friend.username}
                </p>
            </div>

            {children && (
                <div className="flex shrink-0 items-center gap-1">
                    {children}
                </div>
            )}
        </div>
    );
}
