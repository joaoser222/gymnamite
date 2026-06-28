<template>
    <div>
        <div class="d-flex align-center justify-space-between mb-3 ga-3 flex-wrap">
            <div>
                <h3 class="text-h6">{{ title }}</h3>
                <p class="text-body-2 text-medium-emphasis">
                    {{ description }}
                </p>
            </div>
            <v-clipped-button
                color="primary"
                prepend-icon="ti ti-plus"
                @click="addItem"
            >
                {{ addLabel }}
            </v-clipped-button>
        </div>

        <v-alert
            v-if="localItems.length === 0"
            color="secondary"
            variant="tonal"
            border="start"
        >
            Nenhum item adicionado. Clique em <strong>{{ addLabel }}</strong> para continuar.
        </v-alert>

        <v-table
            v-else
            class="border-sm border-surface-variant billable-items-table"
        >
            <thead>
                <tr>
                    <th>Produto</th>
                    <th class="text-right" style="width: 140px">Quantidade</th>
                    <th class="text-right" style="width: 180px">{{ priceLabel }}</th>
                    <th class="text-right" style="width: 180px">Total</th>
                    <th style="width: 72px"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in localItems" :key="item.id ?? `new-${index}`">
                    <td>
                        <ServerAutocomplete
                            :ref="(element) => setProductRef(index, element)"
                            v-model="item.product_id"
                            object-name="product"
                            label="Produto"
                            :error-messages="itemError(index, 'product_id')"
                            @selected-item="applySelectedProduct(index, $event)"
                        />
                    </td>
                    <td>
                        <v-text-field
                            v-model.number="item.quantity"
                            type="number"
                            min="1"
                            label="Quantidade"
                            hide-details="auto"
                            :error-messages="itemError(index, 'quantity')"
                            @update:model-value="handleQuantityChange"
                        />
                    </td>
                    <td>
                        <CurrencyField
                            v-model="item.price"
                            :label="priceLabel"
                            :error-messages="itemError(index, 'price')"
                            @update:model-value="handlePriceChange"
                        />
                    </td>
                    <td>
                        <CurrencyField
                            :model-value="item.total ?? 0"
                            label="Total"
                            readonly
                        />
                    </td>
                    <td class="text-right">
                        <v-btn-icon
                            icon="ti ti-trash"
                            color="error"
                            size="small"
                            @click="removeItem(index)"
                        />
                    </td>
                </tr>
            </tbody>
        </v-table>
    </div>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';

type BillableItem = {
    id?: number;
    product_id?: number | string | null;
    product_name?: string;
    quantity?: number;
    price?: number;
    total?: number;
};

type ProductAutocompleteItem = {
    value: string;
    label: string;
    purchase_price?: number | string | null;
    sale_price?: number | string | null;
};

type FormErrors = Record<string, string | undefined>;

const props = withDefaults(
    defineProps<{
        modelValue?: BillableItem[];
        errors?: FormErrors;
        title?: string;
        description?: string;
        addLabel?: string;
        priceLabel?: string;
        defaultPriceField?: 'purchase_price' | 'sale_price';
    }>(),
    {
        modelValue: () => [],
        errors: () => ({}),
        title: 'Itens',
        description: 'Adicione os produtos e ajuste quantidade e preço de cada item.',
        addLabel: 'Adicionar Item',
        priceLabel: 'Preço',
        defaultPriceField: 'purchase_price',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: BillableItem[]): void;
    (e: 'gross-updated', value: number): void;
}>();

const localItems = ref<BillableItem[]>([]);
const productRefs = ref<unknown[]>([]);
const { show } = useToast();

watch(
    () => props.modelValue,
    (items) => {
        localItems.value = (items ?? []).map((item) => ({ ...item }));
        recalculateTotals(false);
    },
    { immediate: true },
);

function createEmptyItem(): BillableItem {
    return {
        product_id: null,
        product_name: '',
        quantity: 1,
        price: 0,
        total: 0,
    };
}

function normalizeCurrencyValue(value: unknown): number {
    const numberValue = Number(value ?? 0);

    return Number.isFinite(numberValue) ? numberValue : 0;
}

function normalizeQuantity(value: unknown): number {
    const quantity = Number(value ?? 1);

    if (!Number.isFinite(quantity) || quantity < 1) {
        return 1;
    }

    return Math.trunc(quantity);
}

function recalculateTotals(shouldEmit = true): void {
    let grossValue = 0;

    for (const item of localItems.value) {
        item.quantity = normalizeQuantity(item.quantity);
        item.price = normalizeCurrencyValue(item.price);
        item.total = item.price * item.quantity;
        grossValue += item.total;
    }

    if (shouldEmit) {
        emitItems(grossValue);
    }
}

function emitItems(grossValue: number): void {
    emit(
        'update:modelValue',
        localItems.value.map((item) => ({ ...item })),
    );
    emit('gross-updated', grossValue);
}

function addItem(): void {
    const pendingIndex = localItems.value.findIndex((item) => !item.product_id);

    if (pendingIndex !== -1) {
        show({
            type: 'warning',
            message: 'Preencha o produto do item atual antes de adicionar outro.',
        });
        focusProductField(pendingIndex);

        return;
    }

    localItems.value.push(createEmptyItem());
    recalculateTotals();

    void nextTick(() => focusProductField(localItems.value.length - 1));
}

function removeItem(index: number): void {
    localItems.value.splice(index, 1);
    recalculateTotals();
}

function applySelectedProduct(index: number, selectedItem: ProductAutocompleteItem | null): void {
    const item = localItems.value[index];

    if (!item) {
        return;
    }

    if (!selectedItem) {
        item.price = 0;
        item.total = 0;
        recalculateTotals();

        return;
    }

    item.price = normalizeCurrencyValue(selectedItem[props.defaultPriceField]);
    item.total = item.price * normalizeQuantity(item.quantity);
    recalculateTotals();
}

function handleQuantityChange(): void {
    recalculateTotals();
}

function handlePriceChange(): void {
    recalculateTotals();
}

function setProductRef(index: number, element: unknown): void {
    productRefs.value[index] = element;
}

function focusProductField(index: number): void {
    const component = productRefs.value[index] as { $el?: Element; focus?: () => void } | undefined;

    if (!component) {
        return;
    }

    if (typeof component.focus === 'function') {
        component.focus();

        return;
    }

    const input = component.$el?.querySelector('input');

    if (input instanceof HTMLInputElement) {
        input.focus();
    }
}

function itemError(index: number, field: string): string | undefined {
    return props.errors[`items.${index}.${field}`];
}
</script>

<style scoped>
.billable-items-table :deep(th),
.billable-items-table :deep(td) {
    padding-top: 16px !important;
    padding-bottom: 16px !important;
}

.billable-items-table :deep(td) {
    vertical-align: top;
}
</style>
