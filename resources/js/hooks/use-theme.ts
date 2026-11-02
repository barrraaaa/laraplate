import { useEffect, useState } from 'react';

type Theme = 'light' | 'dark';

const STORAGE_KEY = 'theme';

function resolveInitial(): Theme {
    return document.documentElement.classList.contains('dark')
        ? 'dark'
        : 'light';
}

/**
 * Theme state shared by the whole app. The initial value avoids a flash:
 * an inline script in app.blade.php already applied the correct class
 * before hydration.
 */
export function useTheme() {
    const [theme, setThemeState] = useState<Theme>(resolveInitial);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        localStorage.setItem(STORAGE_KEY, theme);
    }, [theme]);

    return { theme, setTheme: setThemeState };
}
