import { Head } from '@inertiajs/react';
import FriendRow from '@/components/friends/friend-row';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { FriendshipPageProps } from '@/types/friendship';

type SectionHeadingProps = {
    title: string;
    count: string;
};

function SectionHeading({ title, count }: SectionHeadingProps) {
    return (
        <div className="mb-3 flex items-baseline justify-between gap-4">
            <h2 className="text-base font-semibold">{title}</h2>

            <span className="text-sm text-muted-foreground">{count}</span>
        </div>
    );
}

export default function Index({
    friends,
    incomingRequests,
    outgoingRequests,
}: FriendshipPageProps) {
    const hasRequests =
        incomingRequests.length > 0 || outgoingRequests.length > 0;

    return (
        <>
            <Head title="Friends" />

            <div className="flex-1 overflow-y-auto">
                <div className="mx-auto w-full max-w-2xl px-6 py-10">
                    <header className="flex items-center justify-between gap-4">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Friends
                        </h1>

                        <Button type="button">Find people</Button>
                    </header>

                    {incomingRequests.length > 0 && (
                        <section className="mt-10">
                            <SectionHeading
                                title="Requests"
                                count={`${incomingRequests.length} pending`}
                            />

                            {incomingRequests.map((request) => (
                                <FriendRow
                                    key={request.id}
                                    friend={request.requester}
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Accept
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-muted-foreground"
                                    >
                                        Decline
                                    </Button>
                                </FriendRow>
                            ))}
                        </section>
                    )}

                    {outgoingRequests.length > 0 && (
                        <section className="mt-10">
                            <SectionHeading
                                title="Sent"
                                count={`${outgoingRequests.length} sent`}
                            />

                            {outgoingRequests.map((request) => (
                                <FriendRow
                                    key={request.id}
                                    friend={request.addressee}
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-muted-foreground"
                                    >
                                        Cancel request
                                    </Button>
                                </FriendRow>
                            ))}
                        </section>
                    )}

                    <section
                        className={cn(
                            'mt-10',
                            hasRequests && 'border-t border-border pt-8',
                        )}
                    >
                        <SectionHeading
                            title="Your friends"
                            count={`${friends.length} ${friends.length === 1 ? 'friend' : 'friends'}`}
                        />

                        {friends.length > 0 ? (
                            friends.map((friend) => (
                                <FriendRow key={friend.id} friend={friend}>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        Remove
                                    </Button>
                                </FriendRow>
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                You haven't added any friends yet.
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
