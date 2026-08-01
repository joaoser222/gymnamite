<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, { type ReadOnlyField } from '@/components/ReadOnlyDetailsPage.vue';
import { formatDateTime } from '@/plugins/formatters';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';
import { usePage } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });
defineProps<{ gatewayInvoice: Record<string, unknown>; routes: { index: string } }>();
const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' }, { title: 'Referência', key: 'gateway_reference_key' },
    { title: 'Status', key: 'status' }, { title: 'Conta Gateway', key: 'gateway_account_id' },
    { title: 'Pagamento Gateway', key: 'gateway_payment_id' }, { title: 'Recebimento', key: 'invoice_id' },
    { title: 'Número', key: 'invoice_number' }, { title: 'Código de validação', key: 'validation_code' },
    { title: 'Descrição do serviço', key: 'service_description' }, { title: 'Valor', key: 'value' },
    { title: 'Data de efetivação', key: 'effective_date' }, { title: 'PDF', key: 'pdf_url' },
    { title: 'XML', key: 'xml_url' },
    { title: 'Criado em', key: 'created_at' }, { title: 'Atualizado em', key: 'updated_at' },
];
const { invoiceStatus } = useSharedOptions(usePage().props.options ?? {});
</script>

<template>
    <ReadOnlyDetailsPage
        title="Nota Fiscal"
        :item="gatewayInvoice"
        :fields="fields"
        :index-route="routes.index"
        :custom-slots="['status', 'created_at', 'updated_at']"
    >
        <template #field-status="{ value }">
            <v-chip :color="findOption(invoiceStatus, value as string)?.color" variant="tonal">
                {{ findLabel(invoiceStatus, value as string) ?? value ?? '-' }}
            </v-chip>
        </template>
        <template #field-created_at="{ value }">{{ formatDateTime(value as string) }}</template>
        <template #field-updated_at="{ value }">{{ formatDateTime(value as string) }}</template>
    </ReadOnlyDetailsPage>
</template>
