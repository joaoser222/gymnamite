<!-- resources/js/Pages/Clients/Index.vue -->
<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import TablePage from '@/components/TablePage.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { masks,formatMasks } from '@/plugins/masks';
defineOptions({ layout: AuthenticatedLayout });

// Props da página
const props = defineProps<{
    clients: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

// Configuração da tabela
const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'CPF', key: 'document',searchable: {
        component: 'MaskedTextField',
        props: {
            mask: masks.cpf
        }
    }},
    { title: 'Telefone', key: 'phone' },
    {
        title: 'Status',
        key: 'status',
        sortable: true,
        align: 'center',
    },
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
        :items="clients.data"
        :total="clients.total"
        :current-page="clients.current_page"
        :last-page="clients.last_page"
        :per-page="clients.per_page"
        :headers="headers"
        :routes="routes"
        module="clients"
        title="Clientes"
        :custom-slots="['status', 'created_at', 'phone', 'document']"
    >
        <!-- Status personalizado -->
        <template #column-status="{ item }">
            <v-chip
                :color="item.status === 'active' ? 'success' : 'error'"
                size="small"
            >
                {{ item.status === 'active' ? 'Ativo' : 'Inativo' }}
            </v-chip>
        </template>
        <template #column-created_at="{ item }">
            {{ new Date(item.created_at).toLocaleDateString('pt-BR') }}
        </template>
        <template #column-document="{ item }">
            {{ formatMasks.cpf(item.document) }}
        </template>
        <template #column-phone="{ item }">
            {{ formatMasks.phone(item.phone) }}
        </template>
    </TablePage>
</template>
