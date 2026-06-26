<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    costCenters: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Tipo', key: 'operation_type', sortable: true },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    create: props.routes.create,
    show: props.routes.show,
    changeVisibility: props.routes.changeVisibility,
    destroy: props.routes.destroy,
};

const sharedProps = usePage().props;
const { operationTypes } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="costCenters.data"
        :total="costCenters.total"
        :current-page="costCenters.current_page"
        :last-page="costCenters.last_page"
        :per-page="costCenters.per_page"
        :headers="headers"
        :routes="routes"
        module="cost_centers"
        title="Centros de Custo"
        :custom-slots="['created_at', 'operation_type']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-operation_type="{ item }">
            {{ findLabel(operationTypes, item.operation_type) }}
        </template>
    </TablePage>
</template>
