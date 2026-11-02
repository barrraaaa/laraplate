import { Head } from '@inertiajs/react';

/**
 * SEO meta per page (rendered through Inertia's <Head> into <head>).
 * Falls back to config/saas.php -> seo defaults shared as props.
 */
export default function Seo({
    title,
    description,
    canonical,
}: {
    title?: string;
    description?: string;
    canonical?: string;
}) {
    const appName = import.meta.env.VITE_APP_NAME || 'Laraplate';
    const fullTitle = title ? `${title} — ${appName}` : appName;

    return (
        <Head>
            <title>{fullTitle}</title>
            {description && <meta name="description" content={description} />}
            {canonical && <link rel="canonical" href={canonical} />}
            <meta property="og:title" content={fullTitle} />
            {description && (
                <meta property="og:description" content={description} />
            )}
            <meta name="twitter:card" content="summary_large_image" />
        </Head>
    );
}
