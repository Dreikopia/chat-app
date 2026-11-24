import { Head, Link, usePage } from '@inertiajs/react';
import { Check, CheckCheck, Search, UserRound } from 'lucide-react';
import { useMemo, useState } from 'react';
import { EmptyChatState } from '@/components/chat/empty-chat-state';
import FriendAvatar from '@/components/friends/friend-avatar';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { cn } from '@/lib/utils';
import { show as chatShow } from '@/routes/chat';
import { index as friendsIndex } from '@/routes/friends';
import type { Auth, ConversationPartner } from '@/types';

type Props = {
    conversations: ConversationPartner[];
};

type Filter = 'all' | 'unread';

function conversationTime(timestamp: string | null | undefined): string {
    if (!timestamp) {
        return '';
    }

    const date = new Date(timestamp);
    const now = new Date();
    const startOfToday = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate(),
    ).getTime();
    const startOfDay = new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
    ).getTime();
    const daysAgo = Math.round((startOfToday - startOfDay) / 86_400_000);

    if (daysAgo === 0) {
        return date.toLocaleTimeString(undefined, {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    }

    if (daysAgo === 1) {
        return 'Yesterday';
    }

    if (daysAgo > 1 && daysAgo < 7) {
        return date.toLocaleDateString(undefined, { weekday: 'short' });
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

function conversationPreview(conversation: ConversationPartner): string {
    if (
        conversation.last_message_is_deleted &&
        !conversation.last_message_is_mine
    ) {
        return 'This message was deleted';
    }

    if (conversation.last_message) {
        return conversation.last_message;
    }

    return `You're now friends with ${conversation.name}.`;
}

function ConversationRow({
    conversation,
}: {
    conversation: ConversationPartner;
}) {
    const unreadCount = conversation.unread_count ?? 0;
    const unread = unreadCount > 0;
    const timestamp = conversationTime(conversation.last_message_at);

    return (
        <Link
            href={chatShow(conversation.id)}
            className="flex min-h-[72px] items-center gap-3 px-4 py-3 transition-colors hover:bg-mine/40 active:bg-mine"
        >
            <span className="relative size-12 shrink-0">
                <FriendAvatar
                    name={conversation.name}
                    avatar={conversation.avatar}
                    className="size-12"
                />

                {conversation.is_active && (
                    <span
                        aria-label="Active"
                        className="absolute right-0 bottom-0 size-3 rounded-full bg-primary ring-2 ring-paper"
                    />
                )}
            </span>

            <div className="min-w-0 flex-1">
                <div className="mb-0.5 flex items-center justify-between gap-2">
                    <div className="flex min-w-0 items-center gap-1.5">
                        <span
                            className={cn(
                                'truncate text-base text-ink',
                                unread ? 'font-semibold' : 'font-medium',
                            )}
                        >
                            {conversation.name}
                        </span>

                        <span className="truncate text-xs text-quiet">
                            @{conversation.username}
                        </span>
                    </div>

                    {timestamp && (
                        <span
                            className={cn(
                                'shrink-0 text-xs',
                                unread
                                    ? 'font-semibold text-primary'
                                    : 'text-quiet',
                            )}
                        >
                            {timestamp}
                        </span>
                    )}
                </div>

                <div className="flex items-center justify-between gap-2">
                    <p
                        className={cn(
                            'flex min-w-0 items-center gap-1.5 text-sm',
                            unread ? 'font-semibold text-ink' : 'text-quiet',
                        )}
                    >
                        {conversation.last_message_is_mine &&
                            conversation.last_message &&
                            (conversation.last_message_is_read ? (
                                <CheckCheck
                                    aria-label="Read"
                                    className="size-4 shrink-0 text-primary"
                                />
                            ) : (
                                <Check
                                    aria-label="Sent"
                                    className="size-4 shrink-0 text-quiet"
                                />
                            ))}

                        <span className="truncate">
                            {conversationPreview(conversation)}
                        </span>
                    </p>

                    {unread && (
                        <span className="flex h-[18px] min-w-5 shrink-0 items-center justify-center rounded-full bg-primary px-1.5 text-xs leading-none font-semibold text-primary-foreground">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}

export default function ChatIndex({ conversations }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [filter, setFilter] = useState<Filter>('all');
    const [search, setSearch] = useState('');

    const activeFriends = useMemo(
        () => conversations.filter((conversation) => conversation.is_active),
        [conversations],
    );

    const filteredConversations = useMemo(() => {
        const term = search.trim().toLowerCase();

        return conversations.filter((conversation) => {
            if (filter === 'unread' && !conversation.unread_count) {
                return false;
            }

            if (!term) {
                return true;
            }

            return [
                conversation.name,
                conversation.username,
                conversation.last_message,
            ].some((value) => value?.toLowerCase().includes(term));
        });
    }, [conversations, filter, search]);

    return (
        <>
            <Head title="Chats" />

            <div className="flex min-h-0 flex-1 flex-col overflow-hidden md:hidden">
                <header className="flex h-16 shrink-0 items-center justify-between border-b border-line/60 px-4">
                    <h1 className="text-xl font-semibold tracking-tight text-ink">
                        Chats
                    </h1>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                type="button"
                                aria-label="Account menu"
                                className="rounded-full focus-visible:outline-offset-4"
                            >
                                <Avatar className="size-8">
                                    <AvatarImage
                                        src={auth.user.avatar}
                                        alt=""
                                        className="object-cover"
                                    />
                                    <AvatarFallback className="bg-primary text-primary-foreground">
                                        <UserRound className="size-4" />
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="w-56">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </header>

                <div className="min-h-0 flex-1 overflow-y-auto pb-[calc(4.5rem+env(safe-area-inset-bottom))]">
                    <div className="px-4 pt-2 pb-3">
                        <div className="relative flex items-center">
                            <Search
                                aria-hidden
                                className="pointer-events-none absolute left-3 size-5 text-quiet"
                            />

                            <input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search chats or people"
                                aria-label="Search chats or people"
                                className="h-10 w-full rounded-xl border border-line bg-surface pr-4 pl-10 text-base text-ink transition-colors placeholder:text-quiet"
                            />
                        </div>
                    </div>

                    {activeFriends.length > 0 && (
                        <section aria-labelledby="active-friends">
                            <div className="mb-2 flex items-center justify-between px-4">
                                <h2
                                    id="active-friends"
                                    className="text-xs text-quiet"
                                >
                                    Active friends
                                </h2>

                                <span className="text-xs text-primary">
                                    {activeFriends.length} online
                                </span>
                            </div>

                            <ul className="flex gap-4 overflow-x-auto px-4 pb-1">
                                {activeFriends.map((friend) => (
                                    <li key={friend.id} className="shrink-0">
                                        <Link
                                            href={chatShow(friend.id)}
                                            className="flex w-14 flex-col items-center gap-1.5"
                                        >
                                            <span className="relative size-12">
                                                <FriendAvatar
                                                    name={friend.name}
                                                    avatar={friend.avatar}
                                                    className="size-12"
                                                />

                                                <span
                                                    aria-hidden
                                                    className="absolute right-0 bottom-0 size-3 rounded-full bg-primary ring-2 ring-paper"
                                                />
                                            </span>

                                            <span className="w-14 truncate text-center text-xs text-ink">
                                                {friend.name.split(' ')[0]}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    <div className="flex items-center gap-2 px-4 pt-3 pb-2">
                        <button
                            type="button"
                            onClick={() => setFilter('all')}
                            aria-pressed={filter === 'all'}
                            className={cn(
                                'rounded-full px-3 py-1 text-xs font-semibold transition-colors',
                                filter === 'all'
                                    ? 'bg-mine text-primary'
                                    : 'border border-line bg-surface text-quiet hover:text-ink',
                            )}
                        >
                            All
                        </button>

                        <button
                            type="button"
                            onClick={() => setFilter('unread')}
                            aria-pressed={filter === 'unread'}
                            className={cn(
                                'rounded-full px-3 py-1 text-xs font-semibold transition-colors',
                                filter === 'unread'
                                    ? 'bg-mine text-primary'
                                    : 'border border-line bg-surface text-quiet hover:text-ink',
                            )}
                        >
                            Unread
                        </button>
                    </div>

                    {filteredConversations.length > 0 ? (
                        <div className="divide-y divide-line/50">
                            {filteredConversations.map((conversation) => (
                                <ConversationRow
                                    key={conversation.id}
                                    conversation={conversation}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="px-4 py-12 text-center">
                            <p className="text-sm text-quiet">
                                {conversations.length === 0
                                    ? 'No chats yet. Find a friend to start.'
                                    : 'No conversations found.'}
                            </p>

                            {conversations.length === 0 && (
                                <Link
                                    href={friendsIndex()}
                                    className="mt-4 inline-flex h-10 items-center rounded-md border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-mine/40"
                                >
                                    Find friends
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>

            <EmptyChatState />
        </>
    );
}
