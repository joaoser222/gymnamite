<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';

defineOptions({ layout: AuthenticatedLayout });

type Report = {
    id?: number;
    name?: string;
    label?: string;
    description?: string;
};

defineProps<{
    report: Report;
    routes: { index: string };
}>();

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' },
    { title: 'Nome', key: 'name' },
    { title: 'Rótulo', key: 'label' },
    { title: 'Descrição', key: 'description', cols: 12 },
];
</script>

<template>
    <ReadOnlyDetailsPage
        title="Relatório"
        :item="report"
        :fields="fields"
        :index-route="routes.index"
    />
</template>
