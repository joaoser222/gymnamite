<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Product = {
    id?: number;
    name?: string;
    purchase_price?: number;
    sale_price?: number;
    quantity?: number;
    product_type?: string;
    product_unity?: string;
};

defineProps<{
    product?: Product | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { productTypes } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    name: '',
    purchase_price: 0,
    sale_price: 0,
    quantity: 0,
    product_type: '',
    product_unity: '',
};
</script>

<template>
    <DetailsPage
        title="Produto"
        :item="product"
        :defaults="defaults"
        :routes="routes"
        module="products"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                        v-text-case="'capitalize'"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-select
                        v-model="form.product_type"
                        label="Tipo"
                        :items="productTypes"
                        :rules="[required]"
                        :error-messages="errors.product_type"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.purchase_price"
                        label="Preço de Compra"
                        type="number"
                        prefix="R$"
                        :error-messages="errors.purchase_price"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.sale_price"
                        label="Preço de Venda"
                        type="number"
                        prefix="R$"
                        :rules="[required]"
                        :error-messages="errors.sale_price"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <ServerAutocomplete
                        v-model="form.product_unity"
                        object-name="product-unity"
                        label="Unidade"
                        :rules="[required]"
                        :error-messages="errors.product_unity"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
