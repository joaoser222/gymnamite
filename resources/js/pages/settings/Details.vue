<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type Setting = {
    id?: number;
    name?: string;
    content?: string;
};

defineProps<{
    setting?: Setting | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
    content: '',
};
</script>

<template>
    <DetailsPage
        title="Configuração"
        :item="setting"
        :defaults="defaults"
        :routes="routes"
        module="settings"
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
                <v-col cols="12">
                    <v-textarea
                        v-model="form.content"
                        label="Conteúdo"
                        rows="10"
                        :error-messages="errors.content"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
