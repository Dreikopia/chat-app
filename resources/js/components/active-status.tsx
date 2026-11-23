import { cn } from '@/lib/utils';

type Props = {
    active?: boolean;
    className?: string;
};

export default function ActiveStatus({ active, className }: Props) {
    if (!active) {
        return null;
    }

    return (
        <span
            className={cn('size-2 shrink-0 rounded-full bg-green-500', className)}
            aria-label="Active"
            title="Active"
        />
    );
}
