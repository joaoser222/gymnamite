<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ClientFormFields from '@/components/clients/ClientFormFields.vue';
import ContractActions from '@/pages/contracts/ContractActions.vue';
import ContractSummary from '@/pages/contracts/ContractSummary.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { formatCurrency, formatDateTime, onlyDigits } from '@/plugins/formatters';
import { masks } from '@/plugins/masks';
import { cpf, required } from '@/plugins/validators';
import { findLabel, findOption, useSharedOptions, type LabeledOption, type Option } from '@/shared/options';
import { useModulePermissions } from '@/composables/useModulePermissions';

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

type Contract = {
    id?: number;
    plan_name?: string;
    modality_quantity?: number | string;
    gross_value?: number;
    discount_value?: number;
    total?: number;
    first_due_date?: string | null;
    installments?: number;
    accepted_terms?: boolean | string | null;
    annotations?: string | null;
    status?: string | null;
    payment_method?: string | null;
    coupon_id?: number | null;
    plan_id?: number;
    client_id?: number;
    created_at?: string | null;
    updated_at?: string | null;
};

type VForm = {
    validate: () => Promise<{ valid: boolean }>;
};

const props = defineProps<{
    contract?: Contract | null;
    cancelRoute?: string | null;
    clientInfo?: string | null;
    couponInfo?: string | null;
    routes: {
        index: string;
        store: string;
        update: string;
        findClient: string;
        findCoupon: string;
    };
    options: {
        genderTypes: LabeledOption<string>[];
        ufs: LabeledOption<string>[];
        plans: PlanOption[];
        billableStatus?: Option[];
        paymentMethods?: Option[];
    };
}>();

const isCreating = !props.contract?.id;

const { genderTypes, ufs, billableStatus, paymentMethods } = useSharedOptions({
    genderTypes: props.options.genderTypes,
    ufs: props.options.ufs,
    billableStatus: props.options.billableStatus,
    paymentMethods: props.options.paymentMethods,
});

const { hasPermission: canCancel, ensurePermissionsLoaded: ensureCancelLoaded } = useModulePermissions<'cancel'>({
    module: () => 'contracts',
    permissions: () => undefined,
    permissionMap: () => undefined,
});

const step = ref(isCreating ? 1 : 2);
const isSearchingClient = ref(false);
const clientLookupState = ref<'idle' | 'found' | 'missing'>('idle');
const clientFormRef = ref<VForm | null>(null);
const contractFormRef = ref<VForm | null>(null);
const lastLoadedDocument = ref('');
const selectedCoupon = ref<CouponOption | null>(null);
const requiresAcceptedTerms = ref(false);

const form = useForm(
    isCreating
        ? {
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
            generate_invoices: false,
        }
        : {
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
            plan_id: props.contract?.plan_id ?? null,
            installments: props.contract?.installments ?? null,
            annotations: props.contract?.annotations ?? '',
            coupon_code: props.couponInfo ?? '',
        },
);

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
    return !requiresAcceptedTerms.value || value || 'Você precisa aceitar os termos da contratação.';
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

if (!isCreating) {
    watch(
        () => form.data(),
        (current, previous) => {
            for (const key of Object.keys(current)) {
                if (key in previous && current[key] !== previous[key] && form.errors[key]) {
                    form.clearErrors(key);
                }
            }
        },
        { deep: true },
    );
}

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

function goBack(){
    if (step.value === 1 || !isCreating) router.get(props.routes.index);
    else step.value -= 1;
}

async function goToContractStep(): Promise<void> {
    const result = await clientFormRef.value?.validate();

    if (result?.valid) {
        step.value = 2;
    }
}

async function submit(generateInvoices = false): Promise<void> {
    if (isCreating) {
        requiresAcceptedTerms.value = generateInvoices;
    }

    const result = await contractFormRef.value?.validate();

    if (!result?.valid) {
        return;
    }

    if (isCreating) {
        form.transform((data) => ({
            ...data,
            generate_invoices: generateInvoices,
        })).post(props.routes.store, {
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
            onFinish: () => form.transform((data) => data),
        });
    } else {
        form.transform((data) => ({
            annotations: data.annotations,
        })).put(props.routes.update.replace(':id', String(props.contract!.id)), {
            preserveScroll: true,
            onFinish: () => form.transform((data) => data),
        });
    }
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

function cancelContract(route: string): void {
    if (!confirm('Tem certeza que deseja cancelar este contrato?')) {
        return;
    }

    router.patch(route, {}, {
        preserveScroll: true,
    });
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

onMounted(() => {
    void ensureCancelLoaded();
});
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between ga-4 my-4 flex-wrap">
            <div>
                <h1 class="text-h5 font-weight-medium">
                    {{ isCreating ? 'Novo contrato' : `Editar contrato #${contract?.id}` }}
                </h1>
            </div>
        </div>

        <v-row>
            <v-col cols="12" lg="8">
                <v-card>
                    <v-card-text class="pb-0">
                        <v-row class="ma-0">
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">ID do contrato</v-label>
                                <div class="text-body-1 mb-3">{{ contract?.id ?? '-' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Status</v-label>
                                <div class="mb-3">
                                    <v-chip :color="findOption(billableStatus, contract?.status)?.color ?? 'secondary'">{{ findLabel(billableStatus, contract?.status) ?? contract?.status ?? '-' }}</v-chip>
                                </div>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-label class="text-caption text-medium-emphasis">Cliente</v-label>
                                <div class="text-body-1 mb-3">{{ clientInfo ?? contract?.client_id ?? '-' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Plano</v-label>
                                <div class="text-body-1 mb-3">{{ contract?.plan_name ?? '-' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Forma de pagamento</v-label>
                                <div class="text-body-1 mb-3">{{ findLabel(paymentMethods, contract?.payment_method) ?? contract?.payment_method ?? '-' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Criado em</v-label>
                                <div class="text-body-1 mb-3">{{ formatDateTime(contract?.created_at) }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Atualizado em</v-label>
                                <div class="text-body-1 mb-3">{{ formatDateTime(contract?.updated_at) }}</div>
                            </v-col>
                        </v-row>
                        <v-divider class="my-4" />
                    </v-card-text>

                    <v-card-text>
                        <v-stepper v-model="step" flat>
                            <v-stepper-header>
                                <v-stepper-item :complete="step > 1 || !isCreating" :value="1" title="Cliente" subtitle="Dados do cliente" />
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
                                                            :disabled="!isCreating"
                                                            :error-messages="form.errors.document"
                                                        />
                                                    </v-col>
                                                    <v-col cols="12" sm="5" md="3" class="pl-2">
                                                        <v-clipped-button
                                                            block
                                                            color="primary"
                                                            prepend-icon="ti ti-search"
                                                            :loading="isSearchingClient"
                                                            :disabled="!isCreating"
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
                                                :disabled="!isCreating"
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
                                                    :disabled="!isCreating"
                                                    :rules="isCreating ? [required] : []"
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
                                                    :disabled="!isCreating || selectedPlan === null"
                                                    :rules="isCreating ? [required] : []"
                                                    :error-messages="form.errors.installments"
                                                />
                                            </v-col>
                                            <v-col cols="12">
                                                <MaskedTextField
                                                    v-model="form.coupon_code"
                                                    label="Cupom"
                                                    :mask="'X*'"
                                                    clearable
                                                    :disabled="!isCreating"
                                                    :error-messages="form.errors.coupon_code"
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
                                            <v-col v-if="isCreating" cols="12">
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

                    <ContractActions
                        :processing="form.processing"
                        :show-continue="step === 1 && isCreating"
                        :show-save="step === 2"
                        :show-finalize="step === 2 && isCreating"
                        :show-cancel="step === 2 && !isCreating && Boolean(contract && cancelRoute && contract.status !== 'canceled' && canCancel('cancel'))"
                        @back="goBack"
                        @continue="goToContractStep"
                        @save="submit(false)"
                        @finalize="submit(true)"
                        @cancel="cancelContract(cancelRoute!)"
                    />
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <ContractSummary
                    :is-creating="isCreating"
                    :client-name="form.name"
                    :client-email="form.email"
                    :client-info="clientInfo"
                    :plan-title="selectedPlan?.title"
                    :plan-name="contract?.plan_name"
                    :plan-category="selectedPlan?.category"
                    :modality-quantity="isCreating ? selectedPlan?.modality_quantity : contract?.modality_quantity"
                    :installments="form.installments"
                    :has-selected-tier="selectedTier !== null"
                    :gross-value="isCreating ? grossValuePreview : contract?.gross_value"
                    :discount-value="isCreating ? discountValuePreview : contract?.discount_value"
                    :total-value="isCreating ? totalValuePreview : contract?.total"
                    :selected-coupon="selectedCoupon"
                    :coupon-info="couponInfo"
                    :discounted-installments-summary="discountedInstallmentsSummary"
                    :coupon-partial-duration-message="couponPartialDurationMessage"
                />
            </v-col>
        </v-row>
    </div>
</template>
