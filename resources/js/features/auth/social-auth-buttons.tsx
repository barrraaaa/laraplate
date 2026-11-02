import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

/**
 * OAuth buttons shared by Login and Register. Providers stay visible
 * even without credentials — unconfigured ones render disabled with a
 * hint, so the feature is discoverable (and honest) rather than hidden.
 */
export default function SocialAuthButtons({
    canLoginWithGoogle,
    canLoginWithGitHub,
}: {
    canLoginWithGoogle: boolean;
    canLoginWithGitHub: boolean;
}) {
    return (
        <>
            <div className="my-5 flex items-center gap-3">
                <Separator className="flex-1" />
                <span className="text-muted-foreground text-xs">or</span>
                <Separator className="flex-1" />
            </div>

            <div className="space-y-2">
                <SocialButton
                    provider="google"
                    enabled={canLoginWithGoogle}
                    href={route('auth.google.redirect')}
                    icon={
                        <svg viewBox="0 0 24 24" className="mr-2 size-4">
                            <path
                                fill="currentColor"
                                d="M21.35 11.1H12v2.9h5.35c-.5 2.5-2.6 4.2-5.35 4.2a5.4 5.4 0 1 1 0-10.8c1.4 0 2.7.5 3.7 1.4l2.1-2.1A8.4 8.4 0 0 0 12 3.6a8.4 8.4 0 1 0 0 16.8c4.7 0 8.1-3.4 8.1-8.1 0-.6 0-1-.1-1.2z"
                            />
                        </svg>
                    }
                >
                    Continue with Google
                </SocialButton>

                <SocialButton
                    provider="github"
                    enabled={canLoginWithGitHub}
                    href={route('auth.github.redirect')}
                    icon={
                        <svg
                            viewBox="0 0 24 24"
                            className="mr-2 size-4"
                            fill="currentColor"
                        >
                            <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.55v-2.17c-3.2.7-3.87-1.36-3.87-1.36-.52-1.33-1.28-1.68-1.28-1.68-1.04-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.76 2.7 1.25 3.36.96.1-.75.4-1.25.73-1.54-2.56-.29-5.25-1.28-5.25-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.78 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.24 2.76.12 3.05.74.81 1.19 1.84 1.19 3.1 0 4.43-2.7 5.41-5.27 5.69.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.55A11.51 11.51 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5Z" />
                        </svg>
                    }
                >
                    Continue with GitHub
                </SocialButton>
            </div>

            {(!canLoginWithGoogle || !canLoginWithGitHub) && (
                <p className="text-muted-foreground mt-3 text-center text-xs">
                    Social sign-in aktif di produksi — set{' '}
                    <code className="font-mono">
                        {missingKeys(canLoginWithGoogle, canLoginWithGitHub)}
                    </code>{' '}
                    di .env untuk mengaktifkannya.
                </p>
            )}
        </>
    );
}

function SocialButton({
    provider,
    enabled,
    href,
    icon,
    children,
}: {
    provider: string;
    enabled: boolean;
    href: string;
    icon: React.ReactNode;
    children: React.ReactNode;
}) {
    const className = cn(
        'w-full',
        !enabled && 'pointer-events-none opacity-55',
    );

    if (enabled) {
        return (
            <Button variant="outline" className={className} asChild>
                <a href={href}>
                    {icon}
                    {children}
                </a>
            </Button>
        );
    }

    return (
        <Button
            variant="outline"
            className={className}
            disabled
            title={`${provider} sign-in requires ${provider === 'google' ? 'GOOGLE_CLIENT_ID' : 'GITHUB_CLIENT_ID'} in .env`}
        >
            {icon}
            {children}
        </Button>
    );
}

function missingKeys(
    canLoginWithGoogle: boolean,
    canLoginWithGitHub: boolean,
): string {
    const missing: string[] = [];

    if (!canLoginWithGoogle)
        missing.push('GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET');
    if (!canLoginWithGitHub)
        missing.push('GITHUB_CLIENT_ID, GITHUB_CLIENT_SECRET');

    return missing.join(' / ');
}
