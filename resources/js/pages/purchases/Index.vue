<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    purchases: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Total', key: 'total', sortable: true },
    { title: 'Status', key: 'status', sortable: true, align: 'center' },
    { title: 'Pagamento', key: 'payment_method', sortable: true },
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
const { billableStatus, paymentMethods } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="purchases.data"
        :total="purchases.total"
        :current-page="purchases.current_page"
        :last-page="purchases.last_page"
        :per-page="purchases.per_page"
        :headers="headers"
        :routes="routes"
        module="purchases"
        title="Compras"
        :custom-slots="['created_at', 'total', 'status', 'payment_method']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-total="{ item }">
            {{ formatCurrency(item.total) }}
        </template>
        <template #column-status="{ item }">
            <v-chip>
                {{ findLabel(billableStatus, item.status) }}
            </v-chip>
        </template>
        <template #column-payment_method="{ item }">
            {{ findLabel(paymentMethods, item.payment_method) }}
        </template>
    </TablePage>
</template>
