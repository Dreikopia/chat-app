export type User = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
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
