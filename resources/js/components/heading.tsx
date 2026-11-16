export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-1'}>
            <h2
                className={
                    variant === 'small'
                        ? 'text-base leading-6 font-semibold'
                        : 'text-2xl leading-8 font-semibold'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="text-sm leading-5 text-muted-foreground">
                    {description}
                </p>
            )}
        </header>
    );
}
