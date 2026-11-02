export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    is_platform_admin?: boolean;
    avatar_url?: string | null;
}

export interface AppNotification {
    id: string;
    title: string;
    body: string;
    url: string | null;
    read: boolean;
    createdAt: string;
}

export interface Organization {
    id: number;
    name: string;
    slug: string | null;
    personal: boolean;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    organization: Organization | null;
    availableOrganizations: Organization[];
    notifications: AppNotification[];
    unreadNotificationsCount: number;
    saas: {
        mode: 'single' | 'team' | 'hybrid';
        features: Record<string, boolean>;
    };
};
