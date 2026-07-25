<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    reports: PaginatedResponse<any>;
    routes: Pick<TableRoutes, 'index' | 'show'>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Rótulo', key: 'label', sortable: true, searchable: true },
    { title: 'Descrição', key: 'description', searchable: true },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.show,
};
</script>

<template>
    <TablePage
        :items="reports.data"
        :total="reports.total"
        :current-page="reports.current_page"
        :last-page="reports.last_page"
        :per-page="reports.per_page"
        :headers="headers"
        :routes="routes"
        module="reports"
        title="Relatórios"
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
