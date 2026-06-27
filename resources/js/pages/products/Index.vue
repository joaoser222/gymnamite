<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    products: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Preço', key: 'sale_price', sortable: true },
    { title: 'Estoque', key: 'quantity', sortable: false },
    { title: 'Tipo', key: 'product_type', sortable: false },
    { title: 'Unidade', key: 'product_unity_label', sortable: false },
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
const { productTypes } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="products.data"
        :total="products.total"
        :current-page="products.current_page"
        :last-page="products.last_page"
        :per-page="products.per_page"
        :headers="headers"
        :routes="routes"
        module="products"
        title="Produtos"
        :custom-slots="['created_at', 'sale_price', 'product_type','product_unity_label']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-sale_price="{ item }">
            {{ formatCurrency(item.sale_price) }}
        </template>
        <template #column-product_type="{ item }">
            <v-chip>
                {{ findLabel(productTypes, item.product_type) }}
            </v-chip>
        </template>
        <template #column-product_unity_label="{ item }">
            <v-chip>
                {{item.product_unity_label}}
            </v-chip>
        </template>
    </TablePage>
</template>
