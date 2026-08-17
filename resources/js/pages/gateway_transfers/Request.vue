<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type RecipientOption = {
    value: number;
    label: string;
};

defineProps<{
    recipients: RecipientOption[];
    routes: DetailsRoutes;
}>();

const defaults = {
    gateway_transfer_recipient_id: null,
    value: null,
    description: '',
};
</script>

<template>
    <DetailsPage
        title="Transferência PIX"
        :defaults="defaults"
        :routes="routes"
        module="gateway_transfers"
        save-label="Solicitar transferência"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12">
                    <v-select
                        v-model="form.gateway_transfer_recipient_id"
                        label="Destinatário"
                        :items="recipients"
                        :rules="[required]"
                        :error-messages="errors.gateway_transfer_recipient_id"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <CurrencyField
                        v-model="form.value"
                        label="Valor"
                        :rules="[required]"
                        :error-messages="errors.value"
                    />
                </v-col>
                <v-col cols="12">
                    <v-textarea
                        v-model="form.description"
                        label="Descrição"
                        rows="3"
                        counter="500"
                        :error-messages="errors.description"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
