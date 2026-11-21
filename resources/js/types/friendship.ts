export type User = {
    id: number;
    name: string;
    username: string;
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
