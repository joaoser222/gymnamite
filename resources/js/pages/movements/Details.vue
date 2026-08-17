<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';
import { formatCurrency } from '@/plugins/formatters';
import { findLabel, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

defineProps<{
    movement: Record<string, unknown>;
    routes: { index: string };
}>();

const sharedProps = usePage().props;
const { operationTypes, movementTypes } = useSharedOptions(sharedProps.options ?? {});

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' },
    { title: 'Tipo de operação', key: 'operation_type' },
    { title: 'Tipo de movimento', key: 'movement_type' },
    { title: 'Valor', key: 'value' },
    { title: 'Fatura', key: 'invoice_id' },
    { title: 'Criado em', key: 'created_at' },
];
</script>

<template>
    <ReadOnlyDetailsPage
        title="Movimento"
        :item="movement"
        :fields="fields"
        :index-route="routes.index"
        :custom-slots="['operation_type', 'movement_type', 'value']"
    >
        <template #field-operation_type="{ value }">
            {{ findLabel(operationTypes, value as string) ?? value }}
        </template>
        <template #field-movement_type="{ value }">
            {{ findLabel(movementTypes, value as string) ?? value }}
        </template>
        <template #field-value="{ value }">
            {{ formatCurrency(value as string | number | null) }}
        </template>
    </ReadOnlyDetailsPage>
</template>
