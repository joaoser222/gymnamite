<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    directLessons: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Data', key: 'lesson_date', sortable: true },
    { title: 'Preço', key: 'price', sortable: true },
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
const { billableStatus } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="directLessons.data"
        :total="directLessons.total"
        :current-page="directLessons.current_page"
        :last-page="directLessons.last_page"
        :per-page="directLessons.per_page"
        :headers="headers"
        :routes="routes"
        module="direct_lessons"
        title="Aulas Diretas"
        :custom-slots="['created_at', 'price', 'lesson_date', 'status']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-price="{ item }">
            {{ formatCurrency(item.price) }}
        </template>
        <template #column-lesson_date="{ item }">
            {{ formatDate(item.lesson_date) }}
        </template>
        <template #column-status="{ item }">
            <v-chip size="small">
                {{ findLabel(billableStatus, item.status) }}
            </v-chip>
        </template>
    </TablePage>
</template>
