<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';
import { formatCurrency, formatDateTime } from '@/plugins/formatters';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

defineProps<{
    gatewayTransfer: Record<string, unknown>;
    routes: { index: string };
}>();

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' },
    { title: 'Referência', key: 'gateway_reference_key' },
    { title: 'Valor bruto', key: 'gross_value' },
    { title: 'Taxa', key: 'fee_value' },
    { title: 'Total', key: 'total' },
    { title: 'Status', key: 'status' },
    { title: 'Conta Gateway', key: 'gateway_account_id' },
    { title: 'Postback', key: 'gateway_postback_id' },
    { title: 'Criado em', key: 'created_at' },
    { title: 'Atualizado em', key: 'updated_at' },
];

const sharedProps = usePage().props;
const { transactionStatus } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <ReadOnlyDetailsPage
        title="Transferência do Gateway"
        :item="gatewayTransfer"
        :fields="fields"
        :index-route="routes.index"
        :custom-slots="[
            'gross_value',
            'fee_value',
            'total',
            'status',
            'created_at',
            'updated_at',
        ]"
    >
        <template #field-gross_value="{ value }">
            {{ formatCurrency(value as string | number | null) }}
        </template>
        <template #field-fee_value="{ value }">
            {{ formatCurrency(value as string | number | null) }}
        </template>
        <template #field-total="{ value }">
            {{ formatCurrency(value as string | number | null) }}
        </template>
        <template #field-status="{ value }">
            <v-chip
                :color="
                    findOption(transactionStatus, value as string | null)?.color
                "
                variant="tonal"
            >
                {{
                    findLabel(transactionStatus, value as string | null) ??
                    value ??
                    '-'
                }}
            </v-chip>
        </template>
        <template #field-created_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
        <template #field-updated_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
    </ReadOnlyDetailsPage>
</template>
