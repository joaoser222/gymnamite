<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Receivable = {
    id?: number;
    holder_id?: number;
    due_date?: string;
    gross_value?: number;
    discount_value?: number;
    interest_value?: number;
    fine_value?: number;
    status?: string;
    payment_method?: string;
    annotations?: string;
    financial_account_id?: number;
    financial_category_id?: number;
    can_request_gateway_invoice?: boolean;
    gateway_invoice_request_reason?: string | null;
};

type PixQrCode = {
    encodedImage?: string;
    payload?: string;
    qrCode?: string;
    expirationDate?: string;
};

const props = defineProps<{
    receivable?: Receivable | null;
    pixQrCode?: PixQrCode | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { invoiceStatus, paymentMethods } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    holder_id: null,
    due_date: '',
    gross_value: 0,
    discount_value: 0,
    interest_value: 0,
    fine_value: 0,
    status: 'pending',
    payment_method: 'cash',
    annotations: '',
    financial_account_id: null,
    financial_category_id: null,
};

const pixQrCodeText = computed(() => props.pixQrCode?.payload ?? props.pixQrCode?.qrCode ?? '');
const pixQrCodeImage = computed(() => {
    const encodedImage = props.pixQrCode?.encodedImage;

    if (!encodedImage) {
        return '';
    }

    return encodedImage.startsWith('data:')
        ? encodedImage
        : `data:image/png;base64,${encodedImage}`;
});

const copyPixCode = async (): Promise<void> => {
    if (!pixQrCodeText.value || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(pixQrCodeText.value);
};

const calculatedTotal = (form: Record<string, unknown>): number => {
    const grossValue = Number(form.gross_value ?? 0);
    const discountValue = Number(form.discount_value ?? 0);
    const interestValue = Number(form.interest_value ?? 0);
    const fineValue = Number(form.fine_value ?? 0);

    return Math.round(((grossValue - discountValue) + interestValue + fineValue) * 100) / 100;
};
</script>

<template>
    <DetailsPage
        title="Recebimento"
        :item="props.receivable"
        :defaults="defaults"
        :routes="props.routes"
        module="receivables"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="4">
                    <ServerAutocomplete
                        v-model="form.holder_id"
                        object-name="client"
                        label="Cliente"
                        :rules="[required]"
                        :error-messages="errors.holder_id"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <DateField
                        v-model="form.due_date"
                        label="Data de Vencimento"
                        :rules="[required]"
                        :error-messages="errors.due_date"
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
                        :model-value="calculatedTotal(form)"
                        label="Total"
                        readonly
                        hint="Calculado automaticamente pelo valor bruto, desconto, juros e multa."
                        persistent-hint
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <ServerAutocomplete
                        v-model="form.financial_account_id"
                        object-name="financial-account"
                        label="Conta"
                        :rules="[required]"
                        :error-messages="errors.financial_account_id"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <ServerAutocomplete
                        v-model="form.financial_category_id"
                        object-name="financial-category"
                        label="Categoria Financeira"
                        :rules="[required]"
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

            <v-card
                v-if="props.pixQrCode"
                class="mt-4"
                variant="tonal"
                color="primary"
            >
                <v-card-title class="d-flex align-center ga-2">
                    <v-icon icon="ti ti-qrcode" />
                    Pagamento PIX
                </v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col v-if="pixQrCodeImage" cols="12" md="3">
                            <v-img
                                :src="pixQrCodeImage"
                                alt="QR Code PIX"
                                max-width="220"
                                class="bg-white rounded pa-2"
                            />
                        </v-col>
                        <v-col cols="12" :md="pixQrCodeImage ? 9 : 12">
                            <v-textarea
                                :model-value="pixQrCodeText"
                                label="PIX copia e cola"
                                readonly
                                rows="4"
                                auto-grow
                                variant="outlined"
                            />
                            <v-btn
                                v-if="pixQrCodeText"
                                color="primary"
                                variant="flat"
                                prepend-icon="ti ti-copy"
                                @click="copyPixCode"
                            >
                                Copiar código PIX
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>
        </template>
        <template #actions>
            <v-btn
                v-if="props.receivable?.can_request_gateway_invoice && props.routes.requestGatewayInvoice"
                color="secondary"
                variant="tonal"
                prepend-icon="ti ti-file-invoice"
                @click="router.post(props.routes.requestGatewayInvoice.replace(':id', String(props.receivable?.id)))"
            >
                Solicitar nota fiscal
            </v-btn>
        </template>
    </DetailsPage>
</template>
