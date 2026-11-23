import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type Props = {
    name: string;
    avatar?: string | null;
    className?: string;
};

export default function FriendAvatar({ name, avatar, className }: Props) {
    const getInitials = useInitials();

    return (
        <Avatar className={cn('size-10 shrink-0', className)}>
            <AvatarImage src={avatar ?? undefined} alt={name} />
            <AvatarFallback className="bg-secondary text-sm font-medium text-primary">
                {getInitials(name)}
            </AvatarFallback>
        </Avatar>
    );
}
