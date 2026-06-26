<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    coupons: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Código', key: 'code', sortable: true, searchable: true },
    { title: 'Desconto (%)', key: 'percent', sortable: true },
    { title: 'Limite', key: 'discount_limit', sortable: true },
    { title: 'Validade', key: 'expiration_date', sortable: true },
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
        :items="coupons.data"
        :total="coupons.total"
        :current-page="coupons.current_page"
        :last-page="coupons.last_page"
        :per-page="coupons.per_page"
        :headers="headers"
        :routes="routes"
        module="coupons"
        title="Cupons"
        :custom-slots="['created_at', 'expiration_date', 'discount_limit']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-expiration_date="{ item }">
            {{ formatDate(item.expiration_date) }}
        </template>
        <template #column-discount_limit="{ item }">
            {{ formatCurrency(item.discount_limit) }}
        </template>
    </TablePage>
</template>
