<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import DetailsPage from '@/components/DetailsPage.vue';
import type { DetailsRoutes } from '@/shared/page';
import MaskedTextField from '@/components/inputs/MaskedTextField.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { masks, phoneMask } from '@/plugins/masks';
import { fillAddressFromCep, type AddressForm } from '@/plugins/viacep';
import { required, email, exactLength, phone, cpf } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Client = {
    id?: number;
    name?: string;
    email?: string;
    phone?: string;
    document?: string;
    gender?: string;
    birth_date?: string;
    legal_representative?: boolean;
    legal_representative_name?: string | null;
    legal_representative_document?: string | null;
    legal_representative_birth_date?: string | null;
    address_postal_code?: string | null;
    address?: string | null;
    address_number?: string | null;
    address_complement?: string | null;
    address_district?: string | null;
    address_state?: string | null;
    address_city?: string | null;
};

defineProps<{
    client?: Client | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { genderTypes, ufs } = useSharedOptions(
    sharedProps.options ?? {},
);

const defaults = {
    name: '',
    email: '',
    phone: '',
    document: '',
    gender: '',
    birth_date: '',
    legal_representative: false,
    legal_representative_name: '',
    legal_representative_document: '',
    legal_representative_birth_date: '',
    address_postal_code: '',
    address: '',
    address_number: '',
    address_complement: '',
    address_district: '',
    address_state: '',
    address_city: ''
};

const isLoadingAddress = ref(false);

async function fillAddress(form: AddressForm): Promise<void> {
    if (isLoadingAddress.value) {
        return;
    }

    isLoadingAddress.value = true;

    try {
        await fillAddressFromCep(form, form.address_postal_code);
    } finally {
        isLoadingAddress.value = false;
    }
}
</script>

<template>
    <DetailsPage
        title="Cliente"
        :item="client"
        :defaults="defaults"
        :routes="routes"
        module="clients"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6" class="d-flex align-center">
                    <v-checkbox
                        v-model="form.legal_representative"
                        label="Possui responsável legal"
                        :error-messages="errors.legal_representative"
                    />
                </v-col>
                <div class="w-100" v-if="form.legal_representative">
                    <v-divider class="my-4">
                        <strong>Dados do Responsável</strong>
                    </v-divider>
                    <v-row class="ma-0">
                        <v-col cols="12">
                            <v-text-field
                                v-model="form.legal_representative_name"
                                label="Nome do responsável"
                                :rules="[required]"
                                :error-messages="
                                    errors.legal_representative_name
                                "
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <MaskedTextField
                                v-model="form.legal_representative_document"
                                label="CPF do responsável"
                                :mask="masks.cpf"
                                :rules="[required, exactLength(11)]"
                                :error-messages="
                                    errors.legal_representative_document
                                "
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.legal_representative_birth_date"
                                label="Nascimento do responsável"
                                type="date"
                                :rules="[required]"
                                :error-messages="
                                    errors.legal_representative_birth_date
                                "
                            />
                        </v-col>
                    </v-row>
                </div>
            </v-row>
            <v-divider class="my-4">
                <strong>Dados Pessoais</strong>
            </v-divider>
            <v-row class="ma-0">
                <v-col cols="12">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.birth_date"
                        label="Nascimento"
                        type="date"
                        :rules="[required]"
                        :error-messages="errors.birth_date"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-select
                        v-model="form.gender"
                        label="Gênero"
                        :items="genderTypes"
                        :rules="[required]"
                        :error-messages="errors.gender"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <MaskedTextField
                        v-model="form.document"
                        label="CPF"
                        :mask="masks.cpf"
                        :rules="[required, cpf]"
                        :error-messages="errors.document"
                    />
                </v-col>
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.email"
                        label="E-mail"
                        type="email"
                        :rules="[required, email]"
                        :error-messages="errors.email"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <MaskedTextField
                        v-model="form.phone"
                        label="Telefone"
                        :mask="phoneMask(form.phone)"
                        :rules="[required, phone]"
                        :error-messages="errors.phone"
                    />
                </v-col>
            </v-row>

            <v-divider class="my-4">
                <strong>Endereço</strong>
            </v-divider>

            <v-row class="ma-0">
                <v-col cols="12" md="4">
                    <MaskedTextField
                        v-model="form.address_postal_code"
                        label="CEP"
                        :mask="masks.cep"
                        :loading="isLoadingAddress"
                        :error-messages="errors.address_postal_code"
                        @blur="fillAddress(form)"
                    />
                </v-col>
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.address"
                        label="Endereço"
                        :error-messages="errors.address"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.address_number"
                        label="Número"
                        :error-messages="errors.address_number"
                    />
                </v-col>
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.address_complement"
                        label="Complemento"
                        :error-messages="errors.address_complement"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.address_district"
                        label="Bairro"
                        :error-messages="errors.address_district"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-select
                        v-model="form.address_state"
                        label="Estado"
                        :items="ufs"
                        :rules="[required]"
                        :error-messages="errors.address_state"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.address_city"
                        label="Cidade"
                        :error-messages="errors.address_city"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
