<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Contract = {
    id?: number;
    plan_name?: string;
    modality_quantity?: number;
    price?: number;
    start_date?: string;
    duration?: number;
    accepted_terms?: boolean;
    annotations?: string;
    plan_id?: number;
    client_id?: number;
};

defineProps<{
    contract?: Contract | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;

const defaults = {
    price: 0,
    start_date: '',
    duration: 0,
    accepted_terms: false,
    annotations: '',
    plan_id: null,
    client_id: null
};
</script>

<template>
    <DetailsPage
        title="Contrato"
        :item="contract"
        :defaults="defaults"
        :routes="routes"
        module="contracts"
    >
        <template #default="{ form, errors }">
            <v-divider class="my-4">
                <strong>Dados do Contrato</strong>
            </v-divider>
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <ServerAutocomplete
                        v-model="form.client_id"
                        object-name="client"
                        label="Cliente"
                        :rules="[required]"
                        :error-messages="errors.client_id"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <ServerAutocomplete
                        v-model="form.plan_id"
                        object-name="plan"
                        label="Plano"
                        :rules="[required]"
                        :error-messages="errors.plan_id"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.price"
                        label="Preço"
                        :rules="[required]"
                        :error-messages="errors.price"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.duration"
                        label="Duração (meses)"
                        type="number"
                        :error-messages="errors.duration"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <DateField
                        v-model="form.start_date"
                        label="Data de Início"
                        :rules="[required]"
                        :error-messages="errors.start_date"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.modality_quantity"
                        label="Quantidade de Modalidades"
                        type="number"
                        :error-messages="errors.modality_quantity"
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
