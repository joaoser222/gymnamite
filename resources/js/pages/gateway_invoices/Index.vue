<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDateTime } from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{ gatewayInvoices: PaginatedResponse<any>; routes: Pick<TableRoutes, 'index' | 'show'> }>();
const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true },
    { title: 'Referência', key: 'gateway_reference_key', searchable: true },
    { title: 'Status', key: 'status', searchable: true },
    { title: 'Recebimento', key: 'invoice_id' },
    { title: 'Número', key: 'invoice_number' },
    { title: 'Valor', key: 'value', sortable: true },
    { title: 'Criado em', key: 'created_at', sortable: true },
];
const { invoiceStatus } = useSharedOptions(usePage().props.options ?? {});
</script>

<template>
    <TablePage
        :items="props.gatewayInvoices.data"
        :total="props.gatewayInvoices.total"
        :current-page="props.gatewayInvoices.current_page"
        :last-page="props.gatewayInvoices.last_page"
        :per-page="props.gatewayInvoices.per_page"
        :headers="headers"
        :routes="props.routes"
        module="gateway_invoices"
        title="Notas Fiscais"
        hide-selection
        hide-visibility-filter
        :permission-map="{ create: false, delete: false, visibility: false }"
        :custom-slots="['status', 'value', 'created_at']"
    >
        <template #column-status="{ item }">
            <v-chip :color="findOption(invoiceStatus, item.status)?.color" variant="tonal">
                {{ findLabel(invoiceStatus, item.status) ?? item.status }}
            </v-chip>
        </template>
        <template #column-value="{ item }">{{ formatCurrency(item.value) }}</template>
        <template #column-created_at="{ item }">{{ formatDateTime(item.created_at) }}</template>
    </TablePage>
</template>
