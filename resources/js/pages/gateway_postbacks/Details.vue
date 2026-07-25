<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';
import { formatDateTime } from '@/plugins/formatters';

defineOptions({ layout: AuthenticatedLayout });

defineProps<{
    gatewayPostback: Record<string, unknown>;
    routes: { index: string };
}>();

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' },
    { title: 'Evento', key: 'postback_event' },
    { title: 'Tipo', key: 'postback_type' },
    { title: 'Status', key: 'status' },
    { title: 'Conta Gateway', key: 'gateway_account_id' },
    { title: 'Payload', key: 'payload', md: 12 },
    { title: 'Criado em', key: 'created_at' },
    { title: 'Atualizado em', key: 'updated_at' },
];
</script>

<template>
    <ReadOnlyDetailsPage
        title="Postback do Gateway"
        :item="gatewayPostback"
        :fields="fields"
        :index-route="routes.index"
        :custom-slots="['payload', 'created_at', 'updated_at']"
    >
        <template #field-payload="{ value }">
            <pre class="text-body-2 overflow-auto">{{ value }}</pre>
        </template>
        <template #field-created_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
        <template #field-updated_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
    </ReadOnlyDetailsPage>
</template>
