<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Movement = {
    id?: number;
    operation_type?: string;
    movement_type?: string;
    value?: number;
    invoice_id?: number;
};

defineProps<{
    movement?: Movement | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { operationTypes, movementTypes } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    operation_type: '',
    movement_type: '',
    value: 0,
    invoice_id: null,
};
</script>

<template>
    <DetailsPage
        title="Movimento"
        :item="movement"
        :defaults="defaults"
        :routes="routes"
        module="movements"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.operation_type"
                        label="Tipo de Operação"
                        :items="operationTypes"
                        :rules="[required]"
                        :error-messages="errors.operation_type"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.movement_type"
                        label="Tipo de Movimento"
                        :items="movementTypes"
                        :rules="[required]"
                        :error-messages="errors.movement_type"
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
            </v-row>
        </template>
    </DetailsPage>
</template>
