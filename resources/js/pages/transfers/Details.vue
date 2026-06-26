<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Transfer = {
    id?: number;
    annotations?: string;
    value?: number;
    status?: string;
    financial_account_id?: number;
};

defineProps<{
    transfer?: Transfer | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { financialAccounts } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    annotations: '',
    value: 0,
    status: 'open',
    financial_account_id: null,
};
</script>

<template>
    <DetailsPage
        title="Transferência"
        :item="transfer"
        :defaults="defaults"
        :routes="routes"
        module="transfers"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.financial_account_id"
                        label="Conta Financeira"
                        :items="financialAccounts"
                        :rules="[required]"
                        :error-messages="errors.financial_account_id"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.value"
                        label="Valor"
                        type="number"
                        prefix="R$"
                        :rules="[required]"
                        :error-messages="errors.value"
                    />
                </v-col>
                <v-col cols="12">
                    <v-textarea
                        v-model="form.annotations"
                        label="Anotações"
                        rows="3"
                        :error-messages="errors.annotations"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
