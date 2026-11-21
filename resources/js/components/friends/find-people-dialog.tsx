import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import FriendRequestController from '@/actions/App/Http/Controllers/FriendRequestController';
import FriendRow from '@/components/friends/friend-row';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { search } from '@/routes/users';
import type { Friendship, User } from '@/types/friendship';

type Props = {
    friends: User[];
    incomingRequests: Friendship[];
    outgoingRequests: Friendship[];
};

type Relationship =
    | { type: 'friend' }
    | { type: 'incoming'; friendship: Friendship }
    | { type: 'outgoing' }
    | { type: 'none' };

export default function FindPeopleDialog({
    friends,
    incomingRequests,
    outgoingRequests,
}: Props) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<User[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [searchError, setSearchError] = useState<string | null>(null);
    const [actionError, setActionError] = useState<string | null>(null);
    const [pendingUserId, setPendingUserId] = useState<number | null>(null);

    const term = query.trim();

    const friendIds = new Set(friends.map((friend) => friend.id));
    const outgoingIds = new Set(
        outgoingRequests.map((request) => request.addressee.id),
    );
    const incomingByUserId = new Map(
        incomingRequests.map((request) => [request.requester.id, request]),
    );

    const relationshipFor = (user: User): Relationship => {
        if (friendIds.has(user.id)) {
            return { type: 'friend' };
        }

        if (outgoingIds.has(user.id)) {
            return { type: 'outgoing' };
        }

        const incoming = incomingByUserId.get(user.id);

        if (incoming) {
            return { type: 'incoming', friendship: incoming };
        }

        return { type: 'none' };
    };

    useEffect(() => {
        if (!open || term.length < 2) {
            setResults([]);
            setIsSearching(false);
            setSearchError(null);

            return;
        }

        const controller = new AbortController();

        setIsSearching(true);
        setSearchError(null);

        const timeout = window.setTimeout(async () => {
            try {
                const response = await fetch(
                    search.url({ query: { q: term } }),
                    {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error('Search failed');
                }

                const users = (await response.json()) as User[];

                if (!controller.signal.aborted) {
                    setResults(users);
                }
            } catch {
                if (!controller.signal.aborted) {
                    setResults([]);
                    setSearchError("Couldn't search right now. Try again.");
                }
            } finally {
                if (!controller.signal.aborted) {
                    setIsSearching(false);
                }
            }
        }, 300);

        return () => {
            window.clearTimeout(timeout);
            controller.abort();
        };
    }, [open, term]);

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (!nextOpen) {
            setQuery('');
            setResults([]);
            setIsSearching(false);
            setSearchError(null);
            setActionError(null);
            setPendingUserId(null);
        }
    };

    const sendRequest = (user: User) => {
        setPendingUserId(user.id);
        setActionError(null);

        router.post(
            FriendRequestController.store.url(user.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) =>
                    setActionError(
                        errors.user ?? 'Could not send the friend request.',
                    ),
                onFinish: () => setPendingUserId(null),
            },
        );
    };

    const acceptRequest = (friendship: Friendship) => {
        setPendingUserId(friendship.requester.id);
        setActionError(null);

        router.patch(
            FriendRequestController.accept.url(friendship.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) =>
                    setActionError(
                        errors.friendship ??
                            'Could not accept the friend request.',
                    ),
                onFinish: () => setPendingUserId(null),
            },
        );
    };

    const renderAction = (user: User) => {
        const relationship = relationshipFor(user);
        const isPending = pendingUserId === user.id;

        if (relationship.type === 'friend') {
            return (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    disabled
                    className="text-muted-foreground"
                >
                    Friends
                </Button>
            );
        }

        if (relationship.type === 'outgoing') {
            return (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    disabled
                    className="text-muted-foreground"
                >
                    Request sent
                </Button>
            );
        }

        if (relationship.type === 'incoming') {
            return (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={isPending}
                    onClick={() => acceptRequest(relationship.friendship)}
                >
                    {isPending ? 'Accepting...' : 'Accept'}
                </Button>
            );
        }

        return (
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={isPending}
                onClick={() => sendRequest(user)}
            >
                {isPending ? 'Sending...' : 'Add'}
            </Button>
        );
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button type="button">Find people</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Find people</DialogTitle>

                    <DialogDescription>
                        Search by username and send a friend request.
                    </DialogDescription>
                </DialogHeader>

                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                    <Input
                        type="search"
                        value={query}
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setActionError(null);
                        }}
                        placeholder="Search by username"
                        aria-label="Search by username"
                        maxLength={20}
                        autoFocus
                        className="pl-9"
                    />

                    {isSearching && (
                        <Spinner className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground" />
                    )}
                </div>

                <div className="min-h-32">
                    {term.length < 2 && (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            Type at least 2 characters to search.
                        </p>
                    )}

                    {term.length >= 2 && searchError && (
                        <p className="py-8 text-center text-sm text-destructive">
                            {searchError}
                        </p>
                    )}

                    {term.length >= 2 &&
                        !searchError &&
                        isSearching &&
                        results.length === 0 && (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                Searching...
                            </p>
                        )}

                    {term.length >= 2 &&
                        !searchError &&
                        !isSearching &&
                        results.length === 0 && (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No users found for @{term}.
                            </p>
                        )}

                    {results.length > 0 && (
                        <div className="max-h-80 divide-y divide-border overflow-y-auto">
                            {results.map((user) => (
                                <FriendRow key={user.id} friend={user}>
                                    {renderAction(user)}
                                </FriendRow>
                            ))}
                        </div>
                    )}
                </div>

                {actionError && (
                    <p className="text-sm text-destructive">{actionError}</p>
                )}
            </DialogContent>
        </Dialog>
    );
}
