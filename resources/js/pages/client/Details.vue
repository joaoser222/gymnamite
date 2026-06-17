<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import DetailsPage, { type DetailsRoutes } from '@/components/DetailsPage.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';

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
    status?: string;
};

type Option = {
    value: string;
    label: string;
};

type SharedProps = {
    enums?: {
        genderTypes?: Option[];
        clientStatus?: Option[];
    };
};

defineProps<{
    client?: Client | null;
    routes: DetailsRoutes;
}>();

const page = usePage();
const sharedProps = page.props as SharedProps;

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
    address_city: '',
    status: 'active',
};

const required = (value: unknown) => !!value || 'Campo obrigatório';
const email = (value: string) => /.+@.+\..+/.test(value) || 'E-mail inválido';
const exactLength = (length: number) => (value: string) =>
    !value || value.length === length || `Informe ${length} caracteres`;

const genderOptions =
    sharedProps.enums?.genderTypes?.map((option) => ({
        title: option.label,
        value: option.value,
    })) ?? [];

const statusOptions =
    sharedProps.enums?.clientStatus?.map((option) => ({
        title: option.label,
        value: option.value,
    })) ?? [];
</script>

<template>
    <DetailsPage
        title="Cliente"
        :item="client"
        :defaults="defaults"
        :routes="routes"
    >
        <template #default="{ form, errors }">
            <v-row>
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
                        v-model="form.email"
                        label="E-mail"
                        type="email"
                        :rules="[required, email]"
                        :error-messages="errors.email"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.phone"
                        label="Telefone"
                        :rules="[required, exactLength(11)]"
                        :error-messages="errors.phone"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.document"
                        label="Documento"
                        :rules="[required, exactLength(11)]"
                        :error-messages="errors.document"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-select
                        v-model="form.gender"
                        label="Gênero"
                        :items="genderOptions"
                        :rules="[required]"
                        :error-messages="errors.gender"
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
                        v-model="form.status"
                        label="Status"
                        :items="statusOptions"
                        :error-messages="errors.status"
                    />
                </v-col>
                <v-col cols="12" md="4" class="d-flex align-center">
                    <v-checkbox
                        v-model="form.legal_representative"
                        label="Possui responsável legal"
                        :error-messages="errors.legal_representative"
                    />
                </v-col>
            </v-row>

            <v-divider class="my-4" />

            <v-row>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.address_postal_code"
                        label="CEP"
                        :error-messages="errors.address_postal_code"
                    />
                </v-col>
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.address"
                        label="Endereço"
                        :error-messages="errors.address"
                    />
                </v-col>
                <v-col cols="12" md="3">
                    <v-text-field
                        v-model="form.address_number"
                        label="Número"
                        :error-messages="errors.address_number"
                    />
                </v-col>
                <v-col cols="12" md="5">
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
                <v-col cols="12" md="3">
                    <v-text-field
                        v-model="form.address_state"
                        label="UF"
                        :error-messages="errors.address_state"
                    />
                </v-col>
                <v-col cols="12" md="9">
                    <v-text-field
                        v-model="form.address_city"
                        label="Cidade"
                        :error-messages="errors.address_city"
                    />
                </v-col>
            </v-row>

            <template v-if="form.legal_representative">
                <v-divider class="my-4" />

                <v-row>
                    <v-col cols="12" md="5">
                        <v-text-field
                            v-model="form.legal_representative_name"
                            label="Nome do responsável"
                            :error-messages="errors.legal_representative_name"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.legal_representative_document"
                            label="Documento do responsável"
                            :rules="[exactLength(11)]"
                            :error-messages="
                                errors.legal_representative_document
                            "
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field
                            v-model="form.legal_representative_birth_date"
                            label="Nascimento do responsável"
                            type="date"
                            :error-messages="
                                errors.legal_representative_birth_date
                            "
                        />
                    </v-col>
                </v-row>
            </template>
        </template>
    </DetailsPage>
</template>
