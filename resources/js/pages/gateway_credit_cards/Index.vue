<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDateTime } from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    gatewayCreditCards: PaginatedResponse<any>;
    routes: Pick<TableRoutes, 'index' | 'show'>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Referência', key: 'gateway_reference_key', searchable: true },
    {
        title: 'Status',
        key: 'status',
        sortable: true,
        searchable: true,
        align: 'center',
    },
    { title: 'Bandeira', key: 'card_brand', sortable: true, searchable: true },
    { title: 'Final', key: 'last_digits', sortable: true, searchable: true },
    { title: 'Conta', key: 'gateway_account_id' },
    { title: 'Cliente', key: 'gateway_customer_id' },
    { title: 'Postback', key: 'gateway_postback_id' },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.show,
};
</script>

<template>
    <TablePage
        :items="gatewayCreditCards.data"
        :total="gatewayCreditCards.total"
        :current-page="gatewayCreditCards.current_page"
        :last-page="gatewayCreditCards.last_page"
        :per-page="gatewayCreditCards.per_page"
        :headers="headers"
        :routes="routes"
        module="gateway_credit_cards"
        title="Cartões do Gateway"
        hide-selection
        :permission-map="{ create: false, delete: false, visibility: false }"
        :custom-slots="['status', 'created_at']"
    >
        <template #column-status="{ item }">
            <v-chip variant="tonal">
                {{ item.status ?? '-' }}
            </v-chip>
        </template>
        <template #column-created_at="{ item }">
            {{ formatDateTime(item.created_at) }}
        </template>
    </TablePage>
</template>
