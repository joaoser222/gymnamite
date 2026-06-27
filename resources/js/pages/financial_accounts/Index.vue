<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    financialAccounts: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Tipo', key: 'account_type', sortable: true },
    { title: 'Saldo', key: 'balance', sortable: true },
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
const { accountTypes } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="financialAccounts.data"
        :total="financialAccounts.total"
        :current-page="financialAccounts.current_page"
        :last-page="financialAccounts.last_page"
        :per-page="financialAccounts.per_page"
        :headers="headers"
        :routes="routes"
        module="financial_accounts"
        title="Contas"
        :custom-slots="['created_at', 'account_type', 'balance']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-account_type="{ item }">
            <v-chip>
                {{ findLabel(accountTypes, item.account_type) }}
            </v-chip>
        </template>
        <template #column-balance="{ item }">
            {{ formatCurrency(item.balance) }}
        </template>
    </TablePage>
</template>
