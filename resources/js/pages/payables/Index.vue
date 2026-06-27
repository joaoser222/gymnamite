<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    payables: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Vencimento', key: 'due_date', sortable: true },
    { title: 'Pagamento', key: 'payment_date', sortable: true },
    { title: 'Total', key: 'total', sortable: true },
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
const { invoiceStatus } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="payables.data"
        :total="payables.total"
        :current-page="payables.current_page"
        :last-page="payables.last_page"
        :per-page="payables.per_page"
        :headers="headers"
        :routes="routes"
        module="payables"
        title="Pagamentos"
        :custom-slots="['created_at', 'total', 'due_date', 'payment_date', 'status']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-total="{ item }">
            {{ formatCurrency(item.total) }}
        </template>
        <template #column-due_date="{ item }">
            {{ formatDate(item.due_date) }}
        </template>
        <template #column-payment_date="{ item }">
            {{ formatDate(item.payment_date) }}
        </template>
        <template #column-status="{ item }">
            <v-chip>
                {{ findLabel(invoiceStatus, item.status) }}
            </v-chip>
        </template>
    </TablePage>
</template>
