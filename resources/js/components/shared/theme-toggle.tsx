import { Button } from '@/components/ui/button';
import { useTheme } from '@/hooks/use-theme';
import { Moon, Sun } from 'lucide-react';

/**
 * Light/dark switch. State is hydrated from localStorage in app.blade.php
 * (inline script, FOUC-safe) and mirrored by the useTheme hook.
 */
export default function ThemeToggle() {
    const { theme, setTheme } = useTheme();

    return (
        <Button
            variant="ghost"
            size="icon"
            aria-label="Toggle theme"
            onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
        >
            <Sun className="hidden size-4 dark:block" />
            <Moon className="block size-4 dark:hidden" />
        </Button>
    );
}
