<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { documentMask, formatMasks } from '@/plugins/masks';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    suppliers: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    {
        title: 'CNPJ/CPF',
        key: 'document',
        searchable: {
            component: 'MaskedTextField',
            props: (value) => ({ mask: documentMask(String(value ?? '')) }),
        },
    },
    { title: 'Telefone', key: 'phone' },
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
        :items="suppliers.data"
        :total="suppliers.total"
        :current-page="suppliers.current_page"
        :last-page="suppliers.last_page"
        :per-page="suppliers.per_page"
        :headers="headers"
        :routes="routes"
        module="suppliers"
        title="Fornecedores"
        :custom-slots="['created_at', 'document', 'phone']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-document="{ item }">
            {{ formatMasks.document(item.document) }}
        </template>
        <template #column-phone="{ item }">
            {{ formatMasks.phone(item.phone) }}
        </template>
    </TablePage>
</template>
