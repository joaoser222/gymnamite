<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    planCategories: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
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
        :items="planCategories.data"
        :total="planCategories.total"
        :current-page="planCategories.current_page"
        :last-page="planCategories.last_page"
        :per-page="planCategories.per_page"
        :headers="headers"
        :routes="routes"
        module="plan_categories"
        title="Categorias de Planos"
        :custom-slots="['created_at']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
    </TablePage>
</template>
