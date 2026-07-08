<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ClientFormFields from '@/components/clients/ClientFormFields.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { formatCurrency, onlyDigits } from '@/plugins/formatters';
import { masks } from '@/plugins/masks';
import { cpf, required } from '@/plugins/validators';
import { useSharedOptions, type LabeledOption } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type PlanTier = {
    quantity: number;
    price: number;
};

type PlanOption = {
    value: number;
    title: string;
    category?: string | null;
    modality_quantity: number;
    tiers: PlanTier[];
};

type ClientLookup = {
    id: number;
    name: string;
    email: string;
    phone: string;
    document: string;
    gender: string;
    birth_date: string;
    legal_representative: boolean;
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
    status?: string | null;
};

type CouponOption = {
    id: number;
    code: string;
    percent?: number | string | null;
    discount_limit?: number | string | null;
    duration?: number | string | null;
    expiration_date?: string | null;
};

type VForm = {
    validate: () => Promise<{ valid: boolean }>;
};

const props = defineProps<{
    routes: {
        index: string;
        store: string;
        findClient: string;
        findCoupon: string;
    };
    options: {
        genderTypes: LabeledOption<string>[];
        ufs: LabeledOption<string>[];
        plans: PlanOption[];
    };
}>();

const { genderTypes, ufs } = useSharedOptions({
    genderTypes: props.options.genderTypes,
    ufs: props.options.ufs,
});

const step = ref(1);
const isSearchingClient = ref(false);
const clientLookupState = ref<'idle' | 'found' | 'missing'>('idle');
const clientFormRef = ref<VForm | null>(null);
const contractFormRef = ref<VForm | null>(null);
const lastLoadedDocument = ref('');
const selectedCoupon = ref<CouponOption | null>(null);

const form = useForm({
    client_id: null as number | null,
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
    coupon_code: '',
    plan_id: null as number | null,
    installments: null as number | null,
    annotations: '',
    accepted_terms: false,
});

const selectedPlan = computed<PlanOption | null>(() => {
    return props.options.plans.find((plan) => plan.value === form.plan_id) ?? null;
});

const durationOptions = computed(() => {
    return (
        selectedPlan.value?.tiers.map((tier) => ({
            title: `${tier.quantity} ${tier.quantity === 1 ? 'mes' : 'meses'} - ${formatCurrency(tier.price)}`,
            value: tier.quantity,
        })) ?? []
    );
});

const selectedTier = computed<PlanTier | null>(() => {
    return (
        selectedPlan.value?.tiers.find(
            (tier) => tier.quantity === Number(form.installments),
        ) ?? null
    );
});

const grossValuePreview = computed(() => {
    if (selectedTier.value === null || form.installments === null) {
        return 0;
    }

    return selectedTier.value.price * Number(form.installments);
});

const grossInstallmentValues = computed(() => {
    const installments = Number(form.installments ?? 0);

    if (!selectedTier.value || installments < 1) {
        return [];
    }

    return splitAmount(grossValuePreview.value, installments);
});

const discountValuePreview = computed(() => {
    if (selectedCoupon.value === null || grossInstallmentValues.value.length === 0) {
        return 0;
    }

    const percent = Number(selectedCoupon.value.percent ?? 0);
    const discountLimit = Number(selectedCoupon.value.discount_limit ?? 0);
    const couponDuration = Number(selectedCoupon.value.duration ?? grossInstallmentValues.value.length);
    const eligibleInstallments = Math.min(
        grossInstallmentValues.value.length,
        Number.isFinite(couponDuration) && couponDuration > 0
            ? couponDuration
            : grossInstallmentValues.value.length,
    );

    const rawDiscounts = grossInstallmentValues.value
        .slice(0, eligibleInstallments)
        .map((value) => value * (percent / 100));

    if (Number.isFinite(discountLimit) && discountLimit > 0) {
        return Math.min(rawDiscounts.reduce((sum, value) => sum + value, 0), discountLimit);
    }

    return rawDiscounts.reduce((sum, value) => sum + value, 0);
});

const totalValuePreview = computed(() => {
    return Math.max(0, grossValuePreview.value - discountValuePreview.value);
});

const couponPartialDurationMessage = computed(() => {
    if (selectedCoupon.value === null || form.installments === null) {
        return null;
    }

    const couponDuration = Number(selectedCoupon.value.duration ?? 0);

    if (!Number.isFinite(couponDuration) || couponDuration <= 0) {
        return null;
    }

    if (Number(form.installments) <= couponDuration) {
        return null;
    }

    return `O cupom ${selectedCoupon.value.code} será aplicado nas primeiras ${selectedCoupon.value.duration} parcelas.`;
});

const discountedInstallmentsSummary = computed(() => {
    if (selectedCoupon.value === null || form.installments === null) {
        return null;
    }

    const couponDuration = Number(selectedCoupon.value.duration ?? 0);

    if (!Number.isFinite(couponDuration) || couponDuration <= 0) {
        return `${form.installments} de ${form.installments} parcelas com desconto.`;
    }

    const discountedInstallments = Math.min(Number(form.installments), couponDuration);

    return `${discountedInstallments} de ${form.installments} parcelas com desconto.`;
});

const acceptedTermsRule = (value: boolean) => {
    return value || 'Você precisa aceitar os termos da contratação.';
};

watch(selectedPlan, (plan) => {
    if (plan === null) {
        form.installments = null;

        return;
    }

    if (!plan.tiers.some((tier) => tier.quantity === Number(form.installments))) {
        form.installments = null;
    }
});

watch(
    () => form.document,
    (value) => {
        const normalized = onlyDigits(value);

        if (normalized !== lastLoadedDocument.value) {
            form.client_id = null;
            clientLookupState.value = 'idle';
        }
    },
);

watch(
    () => form.coupon_code,
    (value) => {
        if (value !== selectedCoupon.value?.code) {
            selectedCoupon.value = null;
        }
    },
);

function applyClient(client: ClientLookup): void {
    form.client_id = client.id;
    form.name = client.name ?? '';
    form.email = client.email ?? '';
    form.phone = client.phone ?? '';
    form.document = client.document ?? '';
    form.gender = client.gender ?? '';
    form.birth_date = client.birth_date ?? '';
    form.legal_representative = Boolean(client.legal_representative);
    form.legal_representative_name = client.legal_representative_name ?? '';
    form.legal_representative_document = client.legal_representative_document ?? '';
    form.legal_representative_birth_date = client.legal_representative_birth_date ?? '';
    form.address_postal_code = client.address_postal_code ?? '';
    form.address = client.address ?? '';
    form.address_number = client.address_number ?? '';
    form.address_complement = client.address_complement ?? '';
    form.address_district = client.address_district ?? '';
    form.address_state = client.address_state ?? '';
    form.address_city = client.address_city ?? '';
    lastLoadedDocument.value = onlyDigits(client.document);
    clientLookupState.value = 'found';
}

function clearClientFields(): void {
    form.client_id = null;
    form.name = '';
    form.email = '';
    form.phone = '';
    form.gender = '';
    form.birth_date = '';
    form.legal_representative = false;
    form.legal_representative_name = '';
    form.legal_representative_document = '';
    form.legal_representative_birth_date = '';
    form.address_postal_code = '';
    form.address = '';
    form.address_number = '';
    form.address_complement = '';
    form.address_district = '';
    form.address_state = '';
    form.address_city = '';
}

async function searchClient(): Promise<void> {
    const document = onlyDigits(form.document);

    if (document.length !== 11) {
        clientLookupState.value = 'idle';

        return;
    }

    isSearchingClient.value = true;

    try {
        const params = new URLSearchParams({ document });
        const response = await fetch(`${props.routes.findClient}?${params}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const payload = (await response.json()) as { client: ClientLookup | null };

        if (payload.client !== null) {
            applyClient(payload.client);

            return;
        }

        clearClientFields();
        form.document = document;
        lastLoadedDocument.value = '';
        clientLookupState.value = 'missing';
    } finally {
        isSearchingClient.value = false;
    }
}

async function goToContractStep(): Promise<void> {
    const result = await clientFormRef.value?.validate();

    if (result?.valid) {
        step.value = 2;
    }
}

async function submit(): Promise<void> {
    const result = await contractFormRef.value?.validate();

    if (!result?.valid) {
        return;
    }

    form.post(props.routes.store, {
        preserveScroll: true,
        onError: (errors) => {
            const contractFields = [
                'coupon_code',
                'plan_id',
                'installments',
                'annotations',
                'accepted_terms',
            ];

            step.value = Object.keys(errors).some((field) => contractFields.includes(field))
                ? 2
                : 1;
        },
    });
}

async function searchCoupon(): Promise<void> {
    const code = String(form.coupon_code ?? '').trim().toUpperCase();

    form.coupon_code = code;

    if (code === '') {
        selectedCoupon.value = null;

        return;
    }

    const params = new URLSearchParams({ code });
    const response = await fetch(`${props.routes.findCoupon}?${params}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    const payload = (await response.json()) as { coupon: CouponOption | null };

    selectedCoupon.value = payload.coupon;
}

function splitAmount(amount: number, installments: number): number[] {
    const scale = 10000;
    const total = Math.round(amount * scale);
    const baseInstallmentValue = Math.floor(total / installments);
    const remainder = total % installments;

    return Array.from({ length: installments }, (_, index) => {
        return (baseInstallmentValue + (index < remainder ? 1 : 0)) / scale;
    });
}
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between ga-4 my-4 flex-wrap">
            <div>
                <h1 class="text-h5 font-weight-medium">Novo contrato</h1>
            </div>

            <v-btn variant="text" prepend-icon="ti ti-arrow-left" @click="router.get(props.routes.index)">
                Voltar para contratos
            </v-btn>
        </div>

        <v-row>
            <v-col cols="12" lg="8">
                <v-card>
                    <v-card-text>
                        <v-stepper v-model="step" flat>
                            <v-stepper-header>
                                <v-stepper-item :complete="step > 1" :value="1" title="Cliente" subtitle="CPF e cadastro" />
                                <v-divider />
                                <v-stepper-item :value="2" title="Contrato" subtitle="Plano e vigência" />
                            </v-stepper-header>

                            <v-stepper-window>
                                <v-stepper-window-item :value="1">
                                    <v-form ref="clientFormRef">
                                        <v-row class="ma-0 mt-4">
                                            <v-col cols="12" class="mb-3">
                                                <v-alert border="start">
                                                    Informe o CPF do cliente. Se ele já existir, os dados serão carregados automaticamente.
                                                </v-alert>
                                            </v-col>
                                            <v-col cols="12">
                                                <v-row dense>
                                                    <v-col cols="12" sm="7" md="9">
                                                        <MaskedTextField
                                                            v-model="form.document"
                                                            label="CPF do cliente"
                                                            :mask="masks.cpf"
                                                            :rules="[required, cpf]"
                                                            :error-messages="form.errors.document"
                                                        />
                                                    </v-col>
                                                    <v-col cols="12" sm="5" md="3" class="pl-2">
                                                        <v-clipped-button
                                                            block
                                                            color="primary"
                                                            prepend-icon="ti ti-search"
                                                            :loading="isSearchingClient"
                                                            @click="searchClient"
                                                        >
                                                            Buscar CPF
                                                        </v-clipped-button>
                                                    </v-col>
                                                </v-row>
                                            </v-col>

                                            <v-col v-if="clientLookupState === 'found'" cols="12">
                                                <v-alert color="success" variant="tonal" border="start">
                                                    Cliente encontrado. Os dados abaixo foram carregados e podem ser ajustados antes de salvar o contrato.
                                                </v-alert>
                                            </v-col>
                                            <v-col v-else-if="clientLookupState === 'missing'" cols="12">
                                                <v-alert color="warning" variant="tonal" border="start">
                                                    Nenhum cliente encontrado para este CPF. Continue preenchendo o cadastro para criar um novo cliente.
                                                </v-alert>
                                            </v-col>
                                            <ClientFormFields
                                                :form="form"
                                                :errors="form.errors"
                                                :gender-types="genderTypes"
                                                :ufs="ufs"
                                            />
                                        </v-row>
                                    </v-form>
                                </v-stepper-window-item>

                                <v-stepper-window-item :value="2">
                                    <v-form ref="contractFormRef">
                                        <v-row class="ma-0 mt-4">
                                            <v-col cols="12" md="6">
                                                <v-select
                                                    v-model="form.plan_id"
                                                    label="Plano"
                                                    :items="props.options.plans"
                                                    item-title="title"
                                                    item-value="value"
                                                    :rules="[required]"
                                                    :error-messages="form.errors.plan_id"
                                                />
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-select
                                                    v-model="form.installments"
                                                    label="Duração"
                                                    :items="durationOptions"
                                                    item-title="title"
                                                    item-value="value"
                                                    :disabled="selectedPlan === null"
                                                    :rules="[required]"
                                                    :error-messages="form.errors.installments"
                                                />
                                            </v-col>
                                            <v-col cols="12">
                                                <MaskedTextField
                                                    v-model="form.coupon_code"
                                                    label="Cupom"
                                                    :mask="'X*'"
                                                    clearable
                                                    :error-messages="form.errors.coupon_code"
                                                    v-text-case="'upper'"
                                                    @blur="searchCoupon"
                                                />
                                            </v-col>
                                            <v-col v-if="selectedCoupon" cols="12">
                                                <v-alert color="info" variant="tonal" border="start">
                                                    <div class="d-flex flex-column ga-1">
                                                        <div>
                                                            Desconto: {{ formatCurrency(discountValuePreview) }}
                                                        </div>
                                                        <div>
                                                            Valor final: {{ formatCurrency(totalValuePreview) }}
                                                        </div>
                                                        <div>
                                                            {{ discountedInstallmentsSummary }}
                                                        </div>
                                                        <div v-if="couponPartialDurationMessage">
                                                            {{ couponPartialDurationMessage }}
                                                        </div>
                                                    </div>
                                                </v-alert>
                                            </v-col>
                                            <v-col cols="12">
                                                <v-textarea
                                                    v-model="form.annotations"
                                                    label="Anotações"
                                                    rows="3"
                                                    :error-messages="form.errors.annotations"
                                                />
                                            </v-col>
                                            <v-col cols="12">
                                                <v-checkbox
                                                    v-model="form.accepted_terms"
                                                    label="Confirmo o aceite dos termos da contratação"
                                                    :rules="[acceptedTermsRule]"
                                                    :error-messages="form.errors.accepted_terms"
                                                />
                                            </v-col>
                                        </v-row>
                                    </v-form>
                                </v-stepper-window-item>
                            </v-stepper-window>
                        </v-stepper>
                    </v-card-text>

                    <v-divider />

                    <v-card-actions class="pa-4 d-flex justify-space-between flex-wrap ga-2">
                        <v-clipped-button color="secondary" prepend-icon="ti ti-arrow-left" :disabled="form.processing" @click="router.get(props.routes.index)">
                            Voltar
                        </v-clipped-button>

                        <div class="d-flex ga-2 flex-wrap">
                            <v-clipped-button v-if="step === 2" color="secondary" prepend-icon="ti ti-arrow-left" :disabled="form.processing" @click="step = 1">
                                Voltar
                            </v-clipped-button>
                            <v-clipped-button v-if="step === 1" color="primary" append-icon="ti ti-arrow-right" @click="goToContractStep">
                                Continuar
                            </v-clipped-button>
                            <v-clipped-button v-else color="primary" prepend-icon="ti ti-device-floppy" :loading="form.processing" @click="submit">
                                Concluir contratação
                            </v-clipped-button>
                        </div>
                    </v-card-actions>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card>
                    <v-card-item>
                        <v-card-title>Resumo</v-card-title>
                        <v-card-subtitle>Confira os dados antes de concluir.</v-card-subtitle>
                    </v-card-item>
                    <v-card-text class="d-flex flex-column ga-4">
                        <div>
                            <div class="text-caption text-medium-emphasis">Cliente</div>
                            <div class="text-body-1 font-weight-medium">{{ form.name || 'Não informado' }}</div>
                            <div class="text-body-2 text-medium-emphasis">{{ form.email || 'Sem e-mail' }}</div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Plano</div>
                            <div class="text-body-1 font-weight-medium">{{ selectedPlan?.title || 'Selecione um plano' }}</div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Categoria</div>
                            <div class="text-body-2 text-medium-emphasis">{{ selectedPlan?.category || 'Sem categoria' }}</div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Qtd. modalidades</div>
                            <div class="text-body-2 text-medium-emphasis">{{ selectedPlan?.modality_quantity ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Duração</div>
                            <div class="text-body-1 font-weight-medium">{{ form.installments ? `${form.installments} meses` : 'Não selecionada' }}</div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Início do contrato</div>
                            <div class="text-body-2 text-medium-emphasis">
                                As parcelas serão geradas a partir de hoje.
                            </div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Valor bruto</div>
                            <div class="text-body-1 font-weight-medium">{{ selectedTier ? formatCurrency(grossValuePreview) : '-' }}</div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Desconto</div>
                            <div class="text-body-1 font-weight-medium">{{ selectedTier ? formatCurrency(discountValuePreview) : '-' }}</div>
                            <div v-if="selectedCoupon" class="text-body-2 text-medium-emphasis">
                                Cupom {{ selectedCoupon.code }} aplicado.
                            </div>
                            <div v-if="discountedInstallmentsSummary" class="text-body-2 text-medium-emphasis">
                                {{ discountedInstallmentsSummary }}
                            </div>
                            <div v-if="couponPartialDurationMessage" class="text-body-2 text-info">
                                {{ couponPartialDurationMessage }}
                            </div>
                        </div>

                        <div>
                            <div class="text-caption text-medium-emphasis">Total final</div>
                            <div class="text-h6 font-weight-bold">{{ selectedTier ? formatCurrency(totalValuePreview) : '-' }}</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>
