<template>
    <div>
        <v-navigation-drawer
            v-model="drawer"
            :temporary="display.mdAndDown.value"
            width="288"
            color="surface"
            elevation="0"
        >
            <div class="py-4 d-flex align-center ga-3">
                <v-img :src="logo" height="48" />
            </div>

            <v-list v-model:opened="openedGroups" nav class="py-2">
                <v-list-group
                    :value="group.name"
                    v-for="group in menu"
                    :key="group.name"
                    class="mb-2"
                >
                    <template #activator="{ props }">
                        <v-list-item v-bind="props">
                            <div class="d-flex flex-row">
                                <v-icon :icon="group.icon" />
                                <div
                                    class="pl-3 text-subtitle-2 font-weight-normal"
                                >
                                    {{ group.title }}
                                </div>
                            </div>
                        </v-list-item>
                    </template>

                    <v-list-item
                        v-for="(item, index) in group.items"
                        :key="index"
                        class="my-2 text-subtitle-2"
                        @click="navigateTo(item.href)"
                        :active="isItemActive(item.href)"
                        :active-class="'text-subtitle-2 font-weight-bold'"
                        active-color="primary"
                    >
                        <template #default>
                            <div class="d-flex flex-row">
                                <v-icon :icon="item.icon" class="pl-4" />
                                <div class="pl-4">{{ item.title }}</div>
                            </div>
                        </template>
                    </v-list-item>
                </v-list-group>
            </v-list>

            <template #append>
                <v-divider />

                <v-list nav density="comfortable">
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

                        <template #append>
                            <v-menu>
                                <template #activator="{ props }">
                                    <v-btn
                                        icon="ti ti-dots-vertical"
                                        variant="text"
                                        size="small"
                                        v-bind="props"
                                    />
                                </template>

                                <v-list density="compact">
                                    <v-list-item
                                        prepend-icon="ti ti-logout"
                                        title="Sair"
                                        @click="logout"
                                    />
                                </v-list>
                            </v-menu>
                        </template>
                    </v-list-item>
                </v-list>
            </template>
        </v-navigation-drawer>

        <v-app-bar flat density="compact" border="b" class="border-surface-variant">
            <v-btn-icon icon="ti ti-menu-2" @click="toggleSidebar" size="small"/>

            <v-app-bar-title>
                <h3>{{ currentPageTitle }}</h3>
            </v-app-bar-title>
        </v-app-bar>

        <v-main>
            <v-img
                cover
                :src="pattern"
            >
                <v-container fluid class="pa-4 pa-md-6 bg-transparent">
                    <slot />
                </v-container>
            </v-img>
        </v-main>
    </div>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useDisplay } from 'vuetify';
import { usePermissions } from '@/composables/usePermissions';
import logo from '@/assets/logo.webp';
import pattern from '@/assets/pattern.jpg';

type MenuItem = {
    title: string;
    icon: string;
    href: string;
};

type MenuGroup = {
    name: string;
    title: string;
    icon: string;
    items: MenuItem[];
};

type AuthUser = {
    id?: number;
    name: string;
    email: string;
    permissions_version?: string | null;
};

type SharedProps = {
    name?: string;
    sidebarOpen?: boolean;
    auth?: {
        user?: AuthUser;
    };
};

const props = defineProps<{
    menu: MenuGroup[];
}>();

const page = usePage();
const display = useDisplay();
const { clearPermissionsCache } = usePermissions();

const sharedProps = computed(() => page.props as SharedProps);

const drawer = ref(sharedProps.value.sidebarOpen ?? true);
const openedGroups = ref<string[]>([]);

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

function openActiveGroup(): void {
    const activeGroup = props.menu.find((group) =>
        group.items.some((item) => isItemActive(item.href)),
    );

    openedGroups.value = activeGroup
        ? [activeGroup.name]
        : props.menu[0]
          ? [props.menu[0].name]
          : [];
}

function persistSidebarState(): void {
    document.cookie = `sidebar_state=${String(drawer.value)}; path=/; max-age=31536000; samesite=lax`;
}

function toggleSidebar(): void {
    drawer.value = !drawer.value;
}

function navigateTo(href: string): void {
    router.visit(href);

    if (display.mdAndDown.value) {
        drawer.value = false;
    }
}

function logout(): void {
    clearPermissionsCache();
    router.post('/logout');
}

watch(currentPath, openActiveGroup, { immediate: true });

watch(
    () => display.mdAndDown.value,
    (isMobile) => {
        if (isMobile) {
            drawer.value = false;
        }
    },
    { immediate: true },
);

watch(drawer, persistSidebarState);
</script>
