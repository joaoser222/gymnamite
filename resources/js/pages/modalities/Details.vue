<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type Modality = {
    id?: number;
    name?: string;
};

defineProps<{
    modality?: Modality | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
};
</script>

<template>
    <DetailsPage
        title="Modalidade"
        :item="modality"
        :defaults="defaults"
        :routes="routes"
        module="modalities"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
