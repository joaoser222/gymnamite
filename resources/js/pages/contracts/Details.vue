<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { useModulePermissions } from '@/composables/useModulePermissions';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { formatCurrency, formatDate, formatDateTime } from '@/plugins/formatters';
import { required } from '@/plugins/validators';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

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

const props = defineProps<{
    contract?: Contract | null;
    routes: DetailsRoutes;
    cancelRoute?: string | null;
    clientInfo?: string | null;
    couponInfo?: string | null;
}>();

const sharedProps = usePage().props;
const { billableStatus, paymentMethods } = useSharedOptions(sharedProps.options ?? {});
const { hasPermission, ensurePermissionsLoaded } = useModulePermissions<'cancel'>({
    module: () => 'contracts',
    permissions: () => undefined,
    permissionMap: () => undefined,
});

const defaults = {
    gross_value: 0,
    discount_value: 0,
    total: 0,
    first_due_date: '',
    installments: 1,
    accepted_terms: false,
    annotations: '',
    payment_method: 'cash',
    plan_id: null,
    client_id: null,
    coupon_id: null,
    status: 'open',
};

const acceptedTermsLabel = computed(() => {
    return props.contract?.accepted_terms === 'accepted' || props.contract?.accepted_terms === true
        ? 'Aceito'
        : 'Pendente';
});

const paymentMethodLabel = computed(() => {
    return findLabel(paymentMethods, props.contract?.payment_method ?? null) ?? props.contract?.payment_method ?? '-';
});

const statusLabel = computed(() => {
    return findLabel(billableStatus, props.contract?.status ?? null) ?? props.contract?.status ?? '-';
});

const statusColor = computed(() => {
    return findOption(billableStatus, props.contract?.status ?? null)?.color ?? 'secondary';
});

function cancelContract(route: string): void {
    if (!confirm('Tem certeza que deseja cancelar este contrato?')) {
        return;
    }

    router.patch(route, {}, {
        preserveScroll: true,
    });
}

onMounted(() => {
    void ensurePermissionsLoaded();
});
</script>

<template>
    <DetailsPage
        title="Contrato"
        :item="contract"
        :defaults="defaults"
        :routes="routes"
        module="contracts"
    >
        <template #default="{ form, errors, isCreating, readonly }">
            <template v-if="isCreating">
                <v-divider class="my-4">
                    <strong>Dados do Contrato</strong>
                </v-divider>
                <v-row class="ma-0">
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
                        <ServerAutocomplete
                            v-model="form.plan_id"
                            object-name="plan"
                            label="Plano"
                            :rules="[required]"
                            :error-messages="errors.plan_id"
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
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.installments"
                            label="Parcelas"
                            type="number"
                            :error-messages="errors.installments"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <DateField
                            v-model="form.first_due_date"
                            label="Primeiro vencimento"
                            :error-messages="errors.first_due_date"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.modality_quantity"
                            label="Quantidade de Modalidades"
                            type="number"
                            :error-messages="errors.modality_quantity"
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

            <template v-else>
                <v-divider class="my-4">
                    <strong>Informações do Contrato</strong>
                </v-divider>
                <v-row class="ma-0">
                    <v-col cols="12" md="3">
                        <v-label class="text-caption text-medium-emphasis">ID do contrato</v-label>
                        <div class="text-body-1 mb-3">{{ contract?.id ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-label class="text-caption text-medium-emphasis">Status</v-label>
                        <div class="mb-3">
                            <v-chip :color="statusColor">{{ statusLabel }}</v-chip>
                        </div>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-label class="text-caption text-medium-emphasis">Cliente</v-label>
                        <div class="text-body-1 mb-3">{{ clientInfo ?? contract?.client_id ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-label class="text-caption text-medium-emphasis">ID do cliente</v-label>
                        <div class="text-body-1 mb-3">{{ contract?.client_id ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-label class="text-caption text-medium-emphasis">Plano</v-label>
                        <div class="text-body-1 mb-3">{{ contract?.plan_name ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-label class="text-caption text-medium-emphasis">ID do plano</v-label>
                        <div class="text-body-1 mb-3">{{ contract?.plan_id ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-label class="text-caption text-medium-emphasis">Cupom</v-label>
                        <div class="text-body-1 mb-3">{{ couponInfo ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-label class="text-caption text-medium-emphasis">ID do cupom</v-label>
                        <div class="text-body-1 mb-3">{{ contract?.coupon_id ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-label class="text-caption text-medium-emphasis">Forma de pagamento</v-label>
                        <div class="text-body-1 mb-3">{{ paymentMethodLabel }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Valor bruto</v-label>
                        <div class="text-body-1 mb-3">{{ formatCurrency(contract?.gross_value) }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Desconto</v-label>
                        <div class="text-body-1 mb-3">{{ formatCurrency(contract?.discount_value) }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Total</v-label>
                        <div class="text-body-1 mb-3">{{ formatCurrency(contract?.total) }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Parcelas</v-label>
                        <div class="text-body-1 mb-3">{{ contract?.installments ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Primeiro vencimento</v-label>
                        <div class="text-body-1 mb-3">{{ formatDate(contract?.first_due_date) }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Quantidade de Modalidades</v-label>
                        <div class="text-body-1 mb-3">{{ contract?.modality_quantity ?? '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Termos aceitos</v-label>
                        <div class="text-body-1 mb-3">{{ acceptedTermsLabel }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Criado em</v-label>
                        <div class="text-body-1 mb-3">{{ formatDateTime(contract?.created_at) }}</div>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-label class="text-caption text-medium-emphasis">Atualizado em</v-label>
                        <div class="text-body-1 mb-3">{{ formatDateTime(contract?.updated_at) }}</div>
                    </v-col>
                </v-row>

                <v-divider class="my-4" />
                <v-row class="ma-0">
                    <v-col cols="12">
                        <v-textarea
                            v-model="form.annotations"
                            label="Anotações"
                            rows="3"
                            :readonly="readonly"
                            :error-messages="errors.annotations"
                        />
                    </v-col>
                </v-row>
            </template>
        </template>
        <template #actions>
            <v-clipped-button
                v-if="contract && cancelRoute && contract.status !== 'canceled' && hasPermission('cancel')"
                color="error"
                prepend-icon="ti ti-x"
                @click="cancelContract(cancelRoute)"
            >
                Cancelar contrato
            </v-clipped-button>
        </template>
    </DetailsPage>
</template>
