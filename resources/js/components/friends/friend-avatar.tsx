import { useInitials } from '@/hooks/use-initials';

type Props = {
    name: string;
};

export default function FriendAvatar({ name }: Props) {
    const getInitials = useInitials();

    return (
        <div
            aria-hidden="true"
            className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-secondary text-sm font-medium text-primary"
        >
            {getInitials(name)}
        </div>
    );
}
