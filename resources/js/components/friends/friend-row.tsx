import type { ReactNode } from 'react';
import ActiveStatus from '@/components/active-status';
import FriendAvatar from '@/components/friends/friend-avatar';
import type { User } from '@/types/friendship';

type Props = {
    friend: User;
    children?: ReactNode;
};

export default function FriendRow({ friend, children }: Props) {
    return (
        <div className="flex items-center gap-3 py-2.5">
            <FriendAvatar name={friend.name} avatar={friend.avatar} />

            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <p className="min-w-0 flex-1 truncate text-sm font-medium">
                        {friend.name}
                    </p>

                    <ActiveStatus active={friend.is_active} />
                </div>

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
