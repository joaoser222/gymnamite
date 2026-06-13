export interface User {
    id: number;
    name: string;
    email: string;
}

export interface FlashToast {
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
}

export interface SharedData {
    name: string;
    auth: {
        user: User | null;
    };
    sidebarOpen: boolean;
    flash: {
        toast: FlashToast | null;
    };
    [key: string]: unknown;
}

export interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginatorLink[];
}

export type VisibilityOption = 'visible' | 'hidden' | 'archived';

export interface FetchParams {
    page: number;
    search: string;
    searchField: string;
    visibility: VisibilityOption;
    sortBy: string;
}

declare module '@inertiajs/core' {
    interface PageProps extends SharedData {}
}

declare module '@inertiajs/react' {
    export function usePage<
        T extends Record<string, unknown> = Record<string, unknown>,
    >(): {
        props: T & SharedData;
        url: string;
        component: string;
    };
}
