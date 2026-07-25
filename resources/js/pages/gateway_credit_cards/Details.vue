<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';
import { formatDateTime } from '@/plugins/formatters';

defineOptions({ layout: AuthenticatedLayout });

defineProps<{
    gatewayCreditCard: Record<string, unknown>;
    routes: { index: string };
}>();

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' },
    { title: 'Token', key: 'gateway_card_token' },
    { title: 'Referência', key: 'gateway_reference_key' },
    { title: 'Status', key: 'status' },
    { title: 'Bandeira', key: 'card_brand' },
    { title: 'Últimos dígitos', key: 'last_digits' },
    { title: 'Conta Gateway', key: 'gateway_account_id' },
    { title: 'Cliente Gateway', key: 'gateway_customer_id' },
    { title: 'Postback', key: 'gateway_postback_id' },
    { title: 'Criado em', key: 'created_at' },
    { title: 'Atualizado em', key: 'updated_at' },
];
</script>

<template>
    <ReadOnlyDetailsPage
        title="Cartão do Gateway"
        :item="gatewayCreditCard"
        :fields="fields"
        :index-route="routes.index"
        :custom-slots="['created_at', 'updated_at']"
    >
        <template #field-created_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
        <template #field-updated_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
    </ReadOnlyDetailsPage>
</template>
