<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Purchase = {
    id?: number;
    total?: number;
    gross_value?: number;
    discount_value?: number;
    status?: string;
    payment_method?: string;
    annotations?: string;
    disable_stock?: boolean;
    supplier_id?: number;
};

defineProps<{
    purchase?: Purchase | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { suppliers, paymentMethods } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    total: 0,
    gross_value: 0,
    discount_value: 0,
    status: 'open',
    payment_method: 'cash',
    annotations: '',
    disable_stock: false,
    supplier_id: null,
};
</script>

<template>
    <DetailsPage
        title="Compra"
        :item="purchase"
        :defaults="defaults"
        :routes="routes"
        module="purchases"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <ServerAutocomplete
                        v-model="form.supplier_id"
                        object-name="supplier"
                        label="Fornecedor"
                        :rules="[required]"
                        :error-messages="errors.supplier_id"
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
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.gross_value"
                        label="Valor Bruto"
                        :error-messages="errors.gross_value"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.discount_value"
                        label="Desconto"
                        :error-messages="errors.discount_value"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.total"
                        label="Total"
                        :rules="[required]"
                        :error-messages="errors.total"
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
