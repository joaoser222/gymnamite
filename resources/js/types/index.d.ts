export interface User {
    id: number;
    name: string;
    email: string;
}

export interface FlashToast {
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
}

export interface EnumOption {
    value: string;
    label: string;
}

export interface SharedEnums {
    accessActions: EnumOption[];
    accessModules: EnumOption[];
    accessRoles: EnumOption[];
    billableStatus: EnumOption[];
    clientStatus: EnumOption[];
    financialAccountTypes: EnumOption[];
    genderTypes: EnumOption[];
    invoiceStatus: EnumOption[];
    invoiceTypes: EnumOption[];
    legalTypes: EnumOption[];
    movementTypes: EnumOption[];
    operationTypes: EnumOption[];
    paymentMethods: EnumOption[];
    postbackStatus: EnumOption[];
    productTypes: EnumOption[];
    transactionStatus: EnumOption[];
    visibility: EnumOption[];
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
    enums: SharedEnums;
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
