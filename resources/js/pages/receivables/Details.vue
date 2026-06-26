<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Receivable = {
    id?: number;
    due_date?: string;
    payment_date?: string;
    gross_value?: number;
    discount_value?: number;
    total?: number;
    status?: string;
    payment_method?: string;
    annotations?: string;
    financial_account_id?: number;
    financial_category_id?: number;
};

defineProps<{
    receivable?: Receivable | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { invoiceStatus, paymentMethods, financialAccounts, financialCategories } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    due_date: '',
    payment_date: '',
    gross_value: 0,
    discount_value: 0,
    total: 0,
    status: 'pending',
    payment_method: 'cash',
    annotations: '',
    financial_account_id: null,
    financial_category_id: null,
};
</script>

<template>
    <DetailsPage
        title="Recebimento"
        :item="receivable"
        :defaults="defaults"
        :routes="routes"
        module="receivables"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.due_date"
                        label="Data de Vencimento"
                        type="date"
                        :rules="[required]"
                        :error-messages="errors.due_date"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.payment_date"
                        label="Data de Pagamento"
                        type="date"
                        :error-messages="errors.payment_date"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-select
                        v-model="form.payment_method"
                        label="Forma de Pagamento"
                        :items="paymentMethods"
                        :error-messages="errors.payment_method"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.gross_value"
                        label="Valor Bruto"
                        type="number"
                        prefix="R$"
                        :error-messages="errors.gross_value"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.discount_value"
                        label="Desconto"
                        type="number"
                        prefix="R$"
                        :error-messages="errors.discount_value"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.total"
                        label="Total"
                        type="number"
                        prefix="R$"
                        :rules="[required]"
                        :error-messages="errors.total"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.financial_account_id"
                        label="Conta Financeira"
                        :items="financialAccounts"
                        :error-messages="errors.financial_account_id"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.financial_category_id"
                        label="Categoria Financeira"
                        :items="financialCategories"
                        :error-messages="errors.financial_category_id"
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
