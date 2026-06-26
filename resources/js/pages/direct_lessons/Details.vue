<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type DirectLesson = {
    id?: number;
    lesson_date?: string;
    price?: number;
    status?: string;
    client_id?: number;
    trainer_id?: number;
};

defineProps<{
    directLesson?: DirectLesson | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { clients, trainers } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    lesson_date: '',
    price: 0,
    status: 'open',
    client_id: null,
    trainer_id: null,
};
</script>

<template>
    <DetailsPage
        title="Aula Direta"
        :item="directLesson"
        :defaults="defaults"
        :routes="routes"
        module="direct_lessons"
    >
        <template #default="{ form, errors }">
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
                        v-model="form.trainer_id"
                        object-name="trainer"
                        label="Treinador"
                        :rules="[required]"
                        :error-messages="errors.trainer_id"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <DateField
                        v-model="form.lesson_date"
                        label="Data da Aula"
                        :rules="[required]"
                        :error-messages="errors.lesson_date"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.price"
                        label="Preço"
                        type="number"
                        prefix="R$"
                        :rules="[required]"
                        :error-messages="errors.price"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
