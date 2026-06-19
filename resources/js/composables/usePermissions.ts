import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Centraliza carregamento e cache local das permissões do usuário autenticado.
type AuthUser = {
    id: number;
    permissions_version: string | null;
};

type SharedProps = {
    auth?: {
        user?: AuthUser | null;
    };
};

type PermissionsCache = {
    userId: number;
    version: string | null;
    permissions: string[];
};

type PermissionsResponse = {
    version: string | null;
    permissions: string[];
};

const CACHE_KEY = 'auth.permissions';

const permissions = ref<string[]>([]);
const ready = ref(false);
const loading = ref(false);

// Evita requisições concorrentes e garante hidratação única por versão de permissão.
let pendingRequest: Promise<string[]> | null = null;
let cacheInitializedForUser: number | null = null;

function isBrowser(): boolean {
    return typeof window !== 'undefined';
}

function readCache(): PermissionsCache | null {
    if (!isBrowser()) {
        return null;
    }

    const cachedValue = window.localStorage.getItem(CACHE_KEY);

    if (cachedValue === null) {
        return null;
    }

    try {
        return JSON.parse(cachedValue) as PermissionsCache;
    } catch {
        window.localStorage.removeItem(CACHE_KEY);

        return null;
    }
}

function writeCache(payload: PermissionsCache): void {
    if (!isBrowser()) {
        return;
    }

    window.localStorage.setItem(CACHE_KEY, JSON.stringify(payload));
}

// Sempre que o usuário muda ou a sessão expira, o cache local também precisa ser descartado.
function clearPermissionsCache(): void {
    permissions.value = [];
    ready.value = false;
    loading.value = false;
    pendingRequest = null;
    cacheInitializedForUser = null;

    if (!isBrowser()) {
        return;
    }

    window.localStorage.removeItem(CACHE_KEY);
}

async function fetchPermissions(): Promise<PermissionsResponse> {
    const response = await fetch('/auth/permissions', {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Failed to load permissions.');
    }

    return (await response.json()) as PermissionsResponse;
}

export function usePermissions() {
    const page = usePage();

    const user = computed(() => {
        return ((page.props as SharedProps).auth?.user ??
            null) as AuthUser | null;
    });

    function hydrateFromCache(): boolean {
        if (!isBrowser() || user.value === null) {
            return false;
        }

        const cachedPermissions = readCache();

        if (
            cachedPermissions === null ||
            cachedPermissions.userId !== user.value.id ||
            cachedPermissions.version !== user.value.permissions_version
        ) {
            return false;
        }

        permissions.value = cachedPermissions.permissions;
        ready.value = true;
        cacheInitializedForUser = user.value.id;

        return true;
    }

    // Tenta cache primeiro e só recorre à API quando necessário ou quando `force` for solicitado.
    async function loadPermissions(force = false): Promise<string[]> {
        if (user.value === null) {
            clearPermissionsCache();

            return [];
        }

        if (
            !force &&
            cacheInitializedForUser === user.value.id &&
            ready.value
        ) {
            return permissions.value;
        }

        if (!force && hydrateFromCache()) {
            return permissions.value;
        }

        if (pendingRequest !== null) {
            return pendingRequest;
        }

        loading.value = true;

        pendingRequest = fetchPermissions()
            .then((payload) => {
                permissions.value = payload.permissions;
                ready.value = true;
                cacheInitializedForUser = user.value?.id ?? null;

                if (user.value !== null) {
                    writeCache({
                        userId: user.value.id,
                        version: payload.version,
                        permissions: payload.permissions,
                    });
                }

                return payload.permissions;
            })
            .finally(() => {
                loading.value = false;
                pendingRequest = null;
            });

        return pendingRequest;
    }

    // Helper semântica para consultas diretas em templates e layouts.
    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    return {
        permissions,
        ready,
        loading,
        can,
        loadPermissions,
        clearPermissionsCache,
    };
}
