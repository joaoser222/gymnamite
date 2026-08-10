<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    roles: PaginatedResponse<any>;
    routes: {
        index: string;
        show: string;
        update: string;
    };
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Descrição', key: 'description' },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.show,
};
</script>

<template>
    <TablePage
        :items="roles.data"
        :total="roles.total"
        :current-page="roles.current_page"
        :last-page="roles.last_page"
        :per-page="roles.per_page"
        :headers="headers"
        :routes="routes"
        module="users"
        title="Perfis"
        hide-selection
        hide-visibility-filter
        :permission-map="{ create: false, delete: false, visibility: false }"
        :custom-slots="['created_at']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
    </TablePage>
</template>
