<!-- resources/js/Pages/Clients/Index.vue -->
<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import TablePage from '@/components/TablePage.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';

defineOptions({ layout: AuthenticatedLayout });

// Props da página
const props = defineProps<{
    clients: {
        data: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}>();

// Configuração da tabela
const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true },
    { title: 'Email', key: 'email', sortable: true },
    { title: 'Telefone', key: 'phone' },
    { 
        title: 'Status', 
        key: 'status', 
        sortable: true,
        align: 'center'
    },
    { title: 'Criado em', key: 'created_at', sortable: true }
];

const routes: TableRoutes = {
    index: '/clients',
    create: '/clients/create',
    edit: (id) => `/clients/${id}/edit`,
    destroy: (id) => `/clients/${id}`
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
        title="Clientes"
        searchable
        creatable
        editable
        deletable
        :custom-slots="['status', 'created_at']"
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
        
        <!-- Data formatada -->
        <template #column-created_at="{ item }">
            {{ new Date(item.created_at).toLocaleDateString('pt-BR') }}
        </template>
        
        <!-- Ações em massa -->
        <template #bulk-actions="{ selected }">
            <v-btn
                color="error"
                variant="text"
                size="small"
                @click="console.log('Excluir selecionados:', selected)"
            >
                Excluir selecionados
            </v-btn>
        </template>
    </TablePage>
</template>