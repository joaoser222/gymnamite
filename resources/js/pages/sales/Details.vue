<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Sale = {
    id?: number;
    total?: number;
    gross_value?: number;
    discount_value?: number;
    status?: string;
    payment_method?: string;
    annotations?: string;
    disable_stock?: boolean;
    client_id?: number;
    items?: Array<Record<string, unknown>>;
};

defineProps<{
    sale?: Sale | null;
    routes: DetailsRoutes;
}>();

const { paymentMethods } = useSharedOptions(usePage().props.options ?? {});

const defaults = {
    total: 0,
    gross_value: 0,
    discount_value: 0,
    status: 'open',
    payment_method: 'cash',
    annotations: '',
    disable_stock: false,
    client_id: null,
    items: [],
};

function normalizeCurrencyValue(value: unknown): number {
    const numberValue = Number(value ?? 0);

    return Number.isFinite(numberValue) ? numberValue : 0;
}

function recalculateTotals(form: Record<string, unknown>, grossValue?: number): void {
    const resolvedGrossValue = normalizeCurrencyValue(grossValue ?? form.gross_value);

    form.gross_value = resolvedGrossValue;
    form.total = resolvedGrossValue - normalizeCurrencyValue(form.discount_value);
}

function validateBillableItems(value: unknown): true | string {
    if (!Array.isArray(value) || value.length === 0) {
        return 'Adicione pelo menos um item.';
    }

    if (value.some((item) => !item || !('product_id' in item) || !item.product_id)) {
        return 'Preencha o produto de todos os itens.';
    }

    return true;
}
</script>

<template>
    <DetailsPage
        title="Venda"
        :item="sale"
        :defaults="defaults"
        :routes="routes"
        module="sales"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" class="ma-0 py-0">
                    <v-switch
                        v-model="form.disable_stock"
                        label="Atualizar estoque"
                        :true-value="false"
                        :false-value="true"
                        color="primary"
                        hide-details
                    />
                    <div class="text-caption text-medium-emphasis mt-1">
                        Quando desativado, esta venda não altera o estoque dos produtos.
                    </div>
                </v-col>
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
                    <v-select
                        v-model="form.payment_method"
                        label="Forma de Pagamento"
                        :items="paymentMethods"
                        :rules="[required]"
                        :error-messages="errors.payment_method"
                    />
                </v-col>
                <v-col cols="12">
                    <BillableItemsTable
                        v-model="form.items"
                        title="Itens da Venda"
                        description="Adicione os produtos e ajuste quantidade e preço de cada item."
                        add-label="Adicionar Item"
                        price-label="Preço de Venda"
                        default-price-field="sale_price"
                        :errors="errors"
                        @gross-updated="recalculateTotals(form, $event)"
                    />
                    <v-input
                        :model-value="form.items"
                        :rules="[validateBillableItems]"
                        :error-messages="errors.items"
                        class="mt-2"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.gross_value"
                        label="Valor Bruto"
                        readonly
                        :error-messages="errors.gross_value"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.discount_value"
                        label="Desconto"
                        :error-messages="errors.discount_value"
                        @update:model-value="recalculateTotals(form)"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.total"
                        label="Total"
                        readonly
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
