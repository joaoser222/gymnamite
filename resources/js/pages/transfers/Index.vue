<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    transfers: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Valor', key: 'value', sortable: true },
    { title: 'Status', key: 'status', sortable: true, align: 'center' },
    { title: 'Anotações', key: 'annotations' },
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
        :items="transfers.data"
        :total="transfers.total"
        :current-page="transfers.current_page"
        :last-page="transfers.last_page"
        :per-page="transfers.per_page"
        :headers="headers"
        :routes="routes"
        module="transfers"
        title="Transferências"
        :custom-slots="['created_at', 'value']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-value="{ item }">
            {{ formatCurrency(item.value) }}
        </template>
    </TablePage>
</template>
