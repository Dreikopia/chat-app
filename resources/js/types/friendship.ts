export type User = {
    id: number;
    name: string;
    username: string;
    avatar: string | null;
    is_active?: boolean;
};

export type Friendship = {
    id: number;
    requester: User;
    addressee: User;
};

export type FriendshipPageProps = {
    friends: User[];
    incomingRequests: Friendship[];
    outgoingRequests: Friendship[];
};
