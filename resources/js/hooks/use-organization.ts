import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export function useAuth() {
    return usePage<PageProps>().props.auth;
}

export function useCurrentOrganization() {
    return usePage<PageProps>().props.organization;
}
