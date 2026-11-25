import { Head } from '@inertiajs/react';
import FindPeopleDialog from '@/components/friends/find-people-dialog';
import FriendRow from '@/components/friends/friend-row';
import IncomingRequestActions from '@/components/friends/incoming-request-actions';
import OutgoingRequestActions from '@/components/friends/outgoing-request-actions';
import RemoveFriendDialog from '@/components/friends/remove-friend-dialog';
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

            <div className="flex-1 overflow-y-auto pb-[calc(4.5rem+env(safe-area-inset-bottom))] md:pb-0">
                <div className="mx-auto w-full max-w-2xl px-4 py-4 md:px-6 md:py-10">
                    <header className="flex items-center justify-between gap-4">
                        <h1 className="text-xl font-semibold tracking-tight md:text-2xl">
                            Friends
                        </h1>

                        <FindPeopleDialog
                            friends={friends}
                            incomingRequests={incomingRequests}
                            outgoingRequests={outgoingRequests}
                        />
                    </header>

                    {incomingRequests.length > 0 && (
                        <section className="mt-10">
                            <SectionHeading
                                title="Friend requests"
                                count={`${incomingRequests.length} pending`}
                            />

                            <div className="divide-y divide-border">
                                {incomingRequests.map((request) => (
                                    <FriendRow
                                        key={request.id}
                                        friend={request.requester}
                                    >
                                        <IncomingRequestActions
                                            request={request}
                                        />
                                    </FriendRow>
                                ))}
                            </div>
                        </section>
                    )}

                    {outgoingRequests.length > 0 && (
                        <section className="mt-10">
                            <SectionHeading
                                title="Sent requests"
                                count={`${outgoingRequests.length} sent`}
                            />

                            <div className="divide-y divide-border">
                                {outgoingRequests.map((request) => (
                                    <FriendRow
                                        key={request.id}
                                        friend={request.addressee}
                                    >
                                        <OutgoingRequestActions
                                            request={request}
                                        />
                                    </FriendRow>
                                ))}
                            </div>
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
                            <div className="divide-y divide-border">
                                {friends.map((friend) => (
                                    <FriendRow key={friend.id} friend={friend}>
                                        <RemoveFriendDialog friend={friend} />
                                    </FriendRow>
                                ))}
                            </div>
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
