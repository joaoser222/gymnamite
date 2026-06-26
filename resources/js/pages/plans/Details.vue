<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type Plan = {
    id?: number;
    name?: string;
    modality_quantity?: number;
    description?: string;
};

defineProps<{
    plan?: Plan | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
    modality_quantity: 0,
    description: '',
};
</script>

<template>
    <DetailsPage
        title="Plano"
        :item="plan"
        :defaults="defaults"
        :routes="routes"
        module="plans"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.modality_quantity"
                        label="Qtd. Modalidades"
                        type="number"
                        :error-messages="errors.modality_quantity"
                    />
                </v-col>
                <v-col cols="12">
                    <v-textarea
                        v-model="form.description"
                        label="Descrição"
                        rows="3"
                        :error-messages="errors.description"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
