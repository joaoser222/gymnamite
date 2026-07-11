<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

type UserListItem = {
    id: number;
    name: string;
    email: string;
    created_at: string | null;
    role?: {
        id: number;
        name: string;
    } | null;
};

const props = defineProps<{
    users: PaginatedResponse<UserListItem>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'E-mail', key: 'email', sortable: true, searchable: true },
    { title: 'Perfil', key: 'role_description' },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    create: props.routes.create,
    show: props.routes.show,
    changeVisibility: props.routes.changeVisibility,
    destroy: props.routes.destroy,
};
</script>

<template>
    <TablePage
        :items="users.data"
        :total="users.total"
        :current-page="users.current_page"
        :last-page="users.last_page"
        :per-page="users.per_page"
        :headers="headers"
        :routes="routes"
        module="users"
        title="Usuários"
        :custom-slots="['role_description', 'created_at']"
    >
        <template #column-role_description="{ item }">
            {{ item.role.description}}
        </template>
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
    </TablePage>
</template>
