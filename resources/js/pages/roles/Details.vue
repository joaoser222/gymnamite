<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type Role = {
    id?: number;
    name?: string;
    description?: string;
};

defineProps<{
    role?: Role | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
    description: '',
};
</script>

<template>
    <DetailsPage
        title="Perfil"
        :item="role"
        :defaults="defaults"
        :routes="routes"
        module="users"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.description"
                        label="Descrição"
                        :error-messages="errors.description"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
