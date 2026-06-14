<!-- resources/js/layouts/AuthenticatedLayout.vue -->
<template>
    <v-app>
        <v-navigation-drawer v-model="drawer" :rail="rail" permanent>
            <v-list-item prepend-icon="ti ti-apps" title="Minha App" nav>
                <template #append>
                    <v-btn
                        :icon="rail ? 'ti ti-chevron-right' : 'ti ti-chevron-left'"
                        variant="text"
                        @click="rail = !rail"
                    />
                </template>
            </v-list-item>

            <v-divider />

            <v-list density="compact" nav>
                <v-list-subheader v-if="!rail">Principal</v-list-subheader>

                <v-list-item
                    v-for="item in mainNav"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :value="item.title"
                    :active="isActive(item.routeName)"
                    active-color="primary"
                    @click="router.visit(item.href)"
                />

                <v-divider v-if="systemNav.length > 0" class="my-2" />
                <v-list-subheader v-if="!rail && systemNav.length > 0"
                    >Sistema</v-list-subheader
                >

                <v-list-item
                    v-for="item in systemNav"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :value="item.title"
                    :active="isActive(item.routeName)"
                    active-color="primary"
                    @click="router.visit(item.href)"
                />
            </v-list>

            <template #append>
                <v-divider />
                <v-list density="compact" nav>
                    <v-list-item
                        :prepend-avatar="userInitials"
                        :title="user.name"
                        :subtitle="!rail ? user.email : undefined"
                    >
                        <template #append>
                            <v-menu v-if="!rail">
                                <template #activator="{ props }">
                                    <v-btn
                                        icon="ti ti-settings"
                                        variant="text"
                                        size="small"
                                        v-bind="props"
                                    />
                                </template>
                                <v-list density="compact">
                                    <v-list-item
                                        prepend-icon="ti ti-logout"
                                        title="Sair"
                                        @click="router.post('/logout')"
                                    />
                                </v-list>
                            </v-menu>
                        </template>
                    </v-list-item>
                </v-list>
            </template>
        </v-navigation-drawer>

        <v-app-bar flat border="b" density="compact">
            <v-app-bar-title>{{ currentPageTitle }}</v-app-bar-title>
            <template #append>
                <v-btn icon="ti ti-bell" variant="text">
                    <v-badge color="primary" floating>
                        <v-icon>ti ti-bell</v-icon>
                    </v-badge>
                </v-btn>
            </template>
        </v-app-bar>

        <v-main>
            <v-container fluid class="pa-6">
                <slot />
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type AuthUser = {
    name: string;
    email: string;
};

type SharedProps = {
    auth?: {
        user?: AuthUser;
    };
};

const page = usePage();

const user = computed(
    () =>
        (page.props as SharedProps).auth?.user ?? {
            name: '',
            email: '',
        },
);

const userInitials = computed(() =>
    user.value.name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase(),
);

const drawer = ref(true);
const rail = ref(false);

const mainNav: Array<{
    title: string;
    icon: string;
    routeName: string;
    href: string;
}> = [
    {
        title: 'Dashboard',
        icon: 'ti ti-home',
        routeName: 'dashboard',
        href: '/dashboard',
    },
    {
        title: 'Clientes',
        icon: 'ti ti-users',
        routeName: 'clients.index',
        href: '/clients',
    },
];

const systemNav: typeof mainNav = [];

const isActive = (routeName: string) =>
    usePage().component === 'Home'
        ? routeName === 'dashboard'
        : window.location.pathname ===
          new URL(
              [...mainNav, ...systemNav].find(
                  (item) => item.routeName === routeName,
              )?.href ?? '/',
              window.location.origin,
          ).pathname;

const currentPageTitle = computed(
    () =>
        [...mainNav, ...systemNav].find((item) => isActive(item.routeName))
            ?.title ?? 'Dashboard',
);
</script>
