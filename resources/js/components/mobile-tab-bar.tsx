import { Link, usePage } from '@inertiajs/react';
import {
    MessagesSquare,
    UserRound,
    UsersRound,
    type LucideIcon,
} from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { index as chatIndex } from '@/routes/chat';
import { index as friendsIndex } from '@/routes/friends';
import { edit as profileEdit } from '@/routes/profile';

type SharedProps = {
    unreadMessagesCount: number;
    pendingFriendRequestsCount: number;
};

type Tab = {
    label: string;
    href: ReturnType<typeof chatIndex>;
    icon: LucideIcon;
    badge: number;
    isActive: boolean;
};

function badgeLabel(count: number): string | null {
    if (count <= 0) {
        return null;
    }

    return count > 9 ? '9+' : String(count);
}

export function MobileTabBar() {
    const page = usePage();
    const { unreadMessagesCount, pendingFriendRequestsCount } =
        usePage<SharedProps>().props;
    const { currentUrl, isCurrentUrl } = useCurrentUrl();

    if (page.component === 'chat/show') {
        return null;
    }

    const tabs: Tab[] = [
        {
            label: 'Chats',
            href: chatIndex(),
            icon: MessagesSquare,
            badge: unreadMessagesCount,
            isActive: isCurrentUrl(chatIndex()),
        },
        {
            label: 'Friends',
            href: friendsIndex(),
            icon: UsersRound,
            badge: pendingFriendRequestsCount,
            isActive: isCurrentUrl(friendsIndex()),
        },
        {
            label: 'Profile',
            href: profileEdit(),
            icon: UserRound,
            badge: 0,
            isActive: currentUrl.startsWith('/settings'),
        },
    ];

    return (
        <nav
            aria-label="Main"
            className="fixed inset-x-0 bottom-0 z-50 border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] md:hidden"
        >
            <div className="flex h-16 items-stretch">
                {tabs.map((tab) => {
                    const badge = badgeLabel(tab.badge);

                    return (
                        <Link
                            key={tab.label}
                            href={tab.href}
                            prefetch
                            aria-current={tab.isActive ? 'page' : undefined}
                            className={cn(
                                'flex min-h-11 flex-1 flex-col items-center justify-center gap-1 text-xs transition-colors',
                                tab.isActive
                                    ? 'text-primary'
                                    : 'text-quiet hover:text-ink',
                            )}
                        >
                            <span className="relative flex items-center justify-center">
                                <tab.icon
                                    className="size-6"
                                    strokeWidth={tab.isActive ? 2.2 : 1.8}
                                />

                                {badge && (
                                    <span
                                        aria-label={
                                            tab.label === 'Chats'
                                                ? `${tab.badge} unread messages`
                                                : `${tab.badge} pending friend requests`
                                        }
                                        className="absolute -top-1.5 -right-3 flex h-[18px] min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[11px] leading-none font-semibold text-primary-foreground"
                                    >
                                        {badge}
                                    </span>
                                )}
                            </span>

                            <span className={cn(tab.isActive && 'font-medium')}>
                                {tab.label}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
