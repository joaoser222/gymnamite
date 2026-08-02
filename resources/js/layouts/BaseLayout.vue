<template>
    <div>
        <v-app-bar
            v-if="!isApplicationsHome"
            flat
            density="compact"
            border="b"
            class="border-surface-variant"
        >
            <v-menu>
                <template #activator="{ props: menuProps }">
                    <v-btn
                        icon="ti ti-apps"
                        variant="text"
                        size="small"
                        v-bind="menuProps"
                    />
                </template>

                <v-list density="compact" min-width="280" max-height="480">
                    <v-list-item
                        prepend-icon="ti ti-home"
                        title="Aplicativos"
                        @click="navigateTo('/')"
                    />
                    <v-divider />
                    <template v-for="group in menu" :key="group.name">
                        <v-list-subheader>{{ group.title }}</v-list-subheader>
                        <v-list-item
                            v-for="item in group.items"
                            :key="item.href"
                            :active="isItemActive(item.href)"
                            :prepend-icon="item.icon"
                            :title="item.title"
                            @click="navigateTo(item.href)"
                        />
                    </template>
                </v-list>
            </v-menu>

            <v-app-bar-title>
                <h3>{{ currentPageTitle }}</h3>
            </v-app-bar-title>

            <v-menu>
                <template #activator="{ props: menuProps }">
                    <v-btn
                        icon="ti ti-user-circle"
                        variant="text"
                        size="small"
                        v-bind="menuProps"
                    />
                </template>

                <v-list density="comfortable" min-width="240">
                    <v-list-item>
                        <template #prepend>
                            <v-avatar color="primary" variant="tonal">
                                <span class="text-subtitle-2">{{
                                    userInitials
                                }}</span>
                            </v-avatar>
                        </template>

                        <v-list-item-title>{{ user.name }}</v-list-item-title>
                        <v-list-item-subtitle>{{
                            user.email
                        }}</v-list-item-subtitle>
                    </v-list-item>
                    <v-divider />
                    <v-list-item
                        prepend-icon="ti ti-logout"
                        title="Sair"
                        @click="logout"
                    />
                </v-list>
            </v-menu>
        </v-app-bar>

        <v-main class="layout-transparent-main">
            <v-container
                fluid
                class="pa-4 pa-md-6 layout-transparent-container"
            >
                <div class="page-content-host">
                    <slot />
                </div>
            </v-container>
        </v-main>
    </div>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import type { MenuGroup } from '@/navigation';

type MenuItem = {
    title: string;
    icon: string;
    href: string;
};

type AuthUser = {
    id?: number;
    name: string;
    email: string;
    permissions_version?: string | null;
};

type SharedProps = {
    name?: string;
    auth?: {
        user?: AuthUser;
    };
};

const props = defineProps<{
    menu: MenuGroup[];
}>();

const page = usePage();
const { clearPermissionsCache } = usePermissions();

const sharedProps = computed(() => page.props as SharedProps);

const appName = computed(() => sharedProps.value.name ?? 'Gymnamite');

const user = computed<AuthUser>(() => {
    return (
        sharedProps.value.auth?.user ?? {
            name: '',
            email: '',
        }
    );
});

const userInitials = computed(() => {
    const initials = user.value.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((namePart) => namePart[0])
        .join('')
        .toUpperCase();

    return initials || 'U';
});

const currentPath = computed(() => {
    const url = page.url || window.location.pathname;

    return new URL(url, window.location.origin).pathname;
});

const isApplicationsHome = computed(() => currentPath.value === '/');

const currentMenuItem = computed(() => {
    return props.menu
        .flatMap((group) => group.items)
        .find((item) => isItemActive(item.href));
});

const currentPageTitle = computed(
    () => currentMenuItem.value?.title ?? 'Dashboard',
);

function normalizePath(href: string): string {
    return new URL(href, window.location.origin).pathname;
}

function isItemActive(href: string): boolean {
    const itemPath = normalizePath(href);

    if (itemPath === '/dashboard') {
        return currentPath.value === itemPath;
    }

    return (
        currentPath.value === itemPath ||
        currentPath.value.startsWith(`${itemPath}/`)
    );
}

function navigateTo(href: string): void {
    router.visit(href);
}

function logout(): void {
    clearPermissionsCache();
    router.post('/logout');
}
</script>
