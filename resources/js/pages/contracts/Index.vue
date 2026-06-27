<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    contracts: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Plano', key: 'plan_name', sortable: true, searchable: true },
    { title: 'Preço', key: 'price', sortable: true },
    { title: 'Início', key: 'start_date', sortable: true },
    { title: 'Duração', key: 'duration' },
    { title: 'Status', key: 'status', sortable: true, align: 'center' },
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
const { billableStatus } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="contracts.data"
        :total="contracts.total"
        :current-page="contracts.current_page"
        :last-page="contracts.last_page"
        :per-page="contracts.per_page"
        :headers="headers"
        :routes="routes"
        module="contracts"
        title="Contratos"
        :custom-slots="['created_at', 'price', 'start_date', 'status']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-price="{ item }">
            {{ formatCurrency(item.price) }}
        </template>
        <template #column-start_date="{ item }">
            {{ formatDate(item.start_date) }}
        </template>
        <template #column-status="{ item }">
            <v-chip>
                {{ findLabel(billableStatus, item.status) }}
            </v-chip>
        </template>
    </TablePage>
</template>
