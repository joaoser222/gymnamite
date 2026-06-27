<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type FinancialAccount = {
    id?: number;
    name?: string;
    account_type?: string;
    balance?: number;
    holder_name?: string;
    holder_document?: string;
    holder_birth_date?: string;
    bank_account_number?: string;
    bank_agency?: string;
    bank_account_type?: string;
    bank_code?: string;
};

defineProps<{
    financialAccount?: FinancialAccount | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { accountTypes } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    name: '',
    account_type: '',
    balance: 0,
    holder_name: '',
    holder_document: '',
    holder_birth_date: '',
    bank_account_number: '',
    bank_agency: '',
    bank_account_type: '',
    bank_code: '',
};
</script>

<template>
    <DetailsPage
        title="Conta"
        :item="financialAccount"
        :defaults="defaults"
        :routes="routes"
        module="financial_accounts"
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
                    <v-select
                        v-model="form.account_type"
                        label="Tipo de Conta"
                        :items="accountTypes"
                        :rules="[required]"
                        :error-messages="errors.account_type"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.balance"
                        label="Saldo"
                        :error-messages="errors.balance"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.holder_name"
                        label="Nome do Titular"
                        :error-messages="errors.holder_name"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.holder_document"
                        label="CPF/CNPJ do Titular"
                        :error-messages="errors.holder_document"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.bank_code"
                        label="Código do Banco"
                        :error-messages="errors.bank_code"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.bank_agency"
                        label="Agência"
                        :error-messages="errors.bank_agency"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.bank_account_number"
                        label="Número da Conta"
                        :error-messages="errors.bank_account_number"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
