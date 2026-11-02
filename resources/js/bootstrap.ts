import { route as routeFn } from 'ziggy-js';

/**
 * Frontend bootstrap — runs once before the Inertia app mounts.
 *
 * The Ziggy route manifest is injected globally by the `@routes` Blade
 * directive (resources/views/app.blade.php), so `route()` stays current
 * without a regenerate step. Typed route names live in
 * resources/js/ziggy.d.ts — refresh them after route changes with
 * `php artisan ziggy:generate --types`.
 */
window.route = routeFn;

declare global {
    interface Window {
        route: typeof routeFn;
    }
}