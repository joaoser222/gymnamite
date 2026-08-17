<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import type { IndexRoutes, PaginatedResponse } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    gatewayTransferRecipients: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Identificação', key: 'label', sortable: true, searchable: true },
    { title: 'Titular', key: 'holder_name', sortable: true, searchable: true },
    { title: 'Documento', key: 'holder_document', searchable: true },
    { title: 'Tipo da Chave PIX', key: 'pix_key_type' },
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
        :items="gatewayTransferRecipients.data"
        :total="gatewayTransferRecipients.total"
        :current-page="gatewayTransferRecipients.current_page"
        :last-page="gatewayTransferRecipients.last_page"
        :per-page="gatewayTransferRecipients.per_page"
        :headers="headers"
        :routes="routes"
        module="gateway_transfer_recipients"
        title="Destinatários de Transferências"
    />
</template>
