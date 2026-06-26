<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    movements: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Tipo Operação', key: 'operation_type', sortable: true },
    { title: 'Tipo Movimento', key: 'movement_type', sortable: true },
    { title: 'Valor', key: 'value', sortable: true },
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
const { operationTypes, movementTypes } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="movements.data"
        :total="movements.total"
        :current-page="movements.current_page"
        :last-page="movements.last_page"
        :per-page="movements.per_page"
        :headers="headers"
        :routes="routes"
        module="movements"
        title="Caixa"
        :custom-slots="['created_at', 'value', 'operation_type', 'movement_type']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-value="{ item }">
            {{ formatCurrency(item.value) }}
        </template>
        <template #column-operation_type="{ item }">
            {{ findLabel(operationTypes, item.operation_type) }}
        </template>
        <template #column-movement_type="{ item }">
            {{ findLabel(movementTypes, item.movement_type) }}
        </template>
    </TablePage>
</template>
