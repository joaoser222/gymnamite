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
    payment_method?: string;
    client_id?: number;
    trainer_id?: number;
};

defineProps<{
    directLesson?: DirectLesson | null;
    routes: DetailsRoutes;
}>();

const { paymentMethods } = useSharedOptions(usePage().props.options ?? {});

const defaults = {
    lesson_date: '',
    price: 0,
    status: 'open',
    payment_method: 'cash',
    generate_invoices: false,
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
        hide-save-action
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
                <v-col cols="12" md="6">
                    <DateField
                        v-model="form.lesson_date"
                        label="Data da Aula"
                        :rules="[required]"
                        :error-messages="errors.lesson_date"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.payment_method"
                        label="Forma de Pagamento"
                        :items="paymentMethods"
                        :rules="[required]"
                        :error-messages="errors.payment_method"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <CurrencyField
                        v-model="form.price"
                        label="Preço"
                        :rules="[required]"
                        :error-messages="errors.price"
                    />
                </v-col>
            </v-row>
        </template>

        <template #actions="{ isCreating, canSave, submit }">
            <v-clipped-button
                v-if="isCreating"
                color="success"
                prepend-icon="ti ti-receipt-2"
                :disabled="!canSave"
                @click="submit({ generate_invoices: true })"
            >
                Finalizar
            </v-clipped-button>
            <v-clipped-button
                color="primary"
                prepend-icon="ti ti-device-floppy"
                :disabled="!canSave"
                @click="submit({ generate_invoices: false })"
            >
                Salvar
            </v-clipped-button>
        </template>
    </DetailsPage>
</template>
