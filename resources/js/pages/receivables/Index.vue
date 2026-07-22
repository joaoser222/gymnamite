<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';
import { required } from '@/plugins/validators';
import { useModulePermissions } from '@/composables/useModulePermissions';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    receivables: PaginatedResponse<any>;
    routes: IndexRoutes & { markPaid: string };
}>();

type Receivable = {
    id: number;
    status: string;
    total: number;
    due_date?: string | null;
};

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Vencimento', key: 'due_date', sortable: true, searchable: { component: 'DateField' } },
    { title: 'Pagamento', key: 'payment_date', sortable: true, searchable: { component: 'DateField' } },
    { title: 'Total', key: 'total', sortable: true },
    {
        title: 'Status',
        key: 'status',
        sortable: true,
        align: 'center',
        searchable: { component: 'VSelect', props: () => ({ items: invoiceStatus }) },
    },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    create: props.routes.create,
    show: props.routes.show,
    changeVisibility: props.routes.changeVisibility,
    destroy: props.routes.destroy,
};

const sharedProps = usePage().props;
const { invoiceStatus } = useSharedOptions(sharedProps.options ?? {});
const settlementDialog = ref(false);
const selectedReceivable = ref<Receivable | null>(null);
const settlementForm = ref({ payment_date: new Date().toISOString().slice(0, 10) });
const settlementErrors = ref<Record<string, string>>({});
const settlementProcessing = ref(false);
const { hasPermission, ensurePermissionsLoaded } = useModulePermissions({
    module: () => 'receivables',
    permissions: () => undefined,
    permissionMap: () => undefined,
});

const canMarkPaid = computed(() => hasPermission('mark_paid'));

const openSettlement = (receivable: Receivable): void => {
    selectedReceivable.value = receivable;
    settlementForm.value = { payment_date: new Date().toISOString().slice(0, 10) };
    settlementErrors.value = {};
    settlementDialog.value = true;
};

const closeSettlement = (): void => {
    settlementDialog.value = false;
    selectedReceivable.value = null;
    settlementErrors.value = {};
};

const settleReceivable = (): void => {
    if (!selectedReceivable.value) {
        return;
    }

    settlementProcessing.value = true;
    router.patch(
        props.routes.markPaid.replace(':id', String(selectedReceivable.value.id)),
        settlementForm.value,
        {
            preserveScroll: true,
            onError: (errors) => {
                settlementErrors.value = errors;
            },
            onSuccess: closeSettlement,
            onFinish: () => {
                settlementProcessing.value = false;
            },
        },
    );
};

onMounted(() => {
    void ensurePermissionsLoaded();
});
</script>

<template>
    <TablePage
        :items="receivables.data"
        :total="receivables.total"
        :current-page="receivables.current_page"
        :last-page="receivables.last_page"
        :per-page="receivables.per_page"
        :headers="headers"
        :routes="routes"
        module="receivables"
        title="Recebimentos"
        :custom-slots="['created_at', 'total', 'due_date', 'payment_date', 'status']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-total="{ item }">
            {{ formatCurrency(item.total) }}
        </template>
        <template #column-due_date="{ item }">
            {{ formatDate(item.due_date) }}
        </template>
        <template #column-payment_date="{ item }">
            {{ formatDate(item.payment_date) }}
        </template>
        <template #column-status="{ item }">
            <v-chip :color="findOption(invoiceStatus, item.status)?.color">
                {{ findLabel(invoiceStatus, item.status) }}
            </v-chip>
        </template>

        <template #extra-actions="{ item }">
            <v-btn-icon
                v-if="canMarkPaid && item.status !== 'paid'"
                icon="ti ti-cash-banknote-plus"
                size="small"
                color="success"
                @click.stop="openSettlement(item)"
            />
        </template>
    </TablePage>

    <v-dialog v-model="settlementDialog" max-width="460">
        <v-card>
            <v-card-title class="d-flex align-center ga-2">
                <v-icon icon="ti ti-cash-banknote-plus" />
                Realizar baixa
            </v-card-title>
            <v-card-text>
                <div v-if="selectedReceivable" class="mb-4">
                    <div class="text-body-2 text-medium-emphasis">Recebimento</div>
                    <div class="text-subtitle-1 font-weight-medium">
                        #{{ selectedReceivable.id }} - {{ formatCurrency(selectedReceivable.total) }}
                    </div>
                </div>

                <DateField
                    v-model="settlementForm.payment_date"
                    label="Data de Pagamento"
                    :rules="[required]"
                    :error-messages="settlementErrors.payment_date"
                />
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn
                    variant="text"
                    :disabled="settlementProcessing"
                    @click="closeSettlement"
                >
                    Cancelar
                </v-btn>
                <v-btn
                    color="success"
                    variant="flat"
                    prepend-icon="ti ti-check"
                    :loading="settlementProcessing"
                    @click="settleReceivable"
                >
                    Confirmar baixa
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
