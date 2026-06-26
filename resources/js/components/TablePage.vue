<!-- resources/js/Components/GenericTable.vue -->
<script setup lang="ts">
import type { FormDataConvertible } from '@inertiajs/core';
import { computed, onMounted, ref, useSlots, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DateField from '@/components/inputs/DateField.vue';
import MaskedTextField from '@/components/inputs/MaskedTextField.vue';
import { VSelect, VTextField } from 'vuetify/components';
import {
    useModulePermissions,
    type ModulePermissionMap,
} from '@/composables/useModulePermissions';
import { visibilityOptions, type VisibilityValue } from '@/shared/visibility';
import type { PaginatedResponse } from '@/shared/page';

/**
 * Tabela genérica para páginas indexadas do sistema.
 *
 * Responsabilidades:
 * - renderizar busca, filtros, seleção, ações e paginação;
 * - sincronizar filtros com a URL via Inertia;
 * - aplicar permissões de visualização, criação, exclusão e visibilidade;
 * - permitir colunas e ações extras via slots.
 */

// ─── Tipos ───────────────────────────────────────────────────────────────────

type SearchValue = string | number | boolean | null;

type SearchComponentProps =
    | Record<string, unknown>
    | ((value: SearchValue) => Record<string, unknown>);

type SearchComponentName =
    | 'VTextField'
    | 'VSelect'
    | 'DateField'
    | 'MaskedTextField';

type SearchableConfig = {
    component?: SearchComponentName;
    props?: SearchComponentProps;
};

export type { PaginatedResponse };

export type TableHeader = {
    title: string;
    key: string;
    sortable?: boolean;
    align?: 'start' | 'center' | 'end';
    width?: string;
    searchable?: boolean | SearchableConfig;
};

export type TableRoutes = {
    index: string;
    create?: string;
    show?: string;
    destroy?: string;
    changeVisibility?: string;
};

type TablePermissionAction = 'view' | 'create' | 'delete' | 'visibility';
type TablePermissionMap = ModulePermissionMap<TablePermissionAction>;

type SharedProps = {
    filters?: {
        visibility?: string;
        search?: SearchValue;
        searchField?: string;
    };
};

// ─── Registro de componentes de busca ────────────────────────────────────────

const searchComponentRegistry = {
    VTextField,
    VSelect,
    DateField,
    MaskedTextField,
} as const;

// ─── Props / Emits ───────────────────────────────────────────────────────────

interface Props {
    items: unknown[];
    total: number;
    currentPage: number;
    lastPage: number;
    perPage: number;
    headers: TableHeader[];
    routes?: TableRoutes;
    hideSelection?: boolean;
    loading?: boolean;
    searchKey?: string;
    title?: string;
    // Permissões: o padrão é derivar do módulo; `permissions` e `permissionMap` permitem sobrescrever.
    module?: string;
    permissions?: string[];
    permissionMap?: TablePermissionMap;
    customSlots?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    hideSelection: false,
    loading: false,
    searchKey: 'search',
    title: 'Items',
    customSlots: () => [],
});

// Eventos para páginas que precisem reagir sem duplicar a lógica da tabela.
const emit = defineEmits<{
    'update:search': [value: SearchValue];
    'update:page': [value: number];
    'update:perPage': [value: number];
    edit: [item: unknown];
    delete: [ids: number[]];
    'change:visibility': [ids: number[], visibility: string];
    create: [];
    selection: [items: unknown[]];
    reload: [];
}>();

// ─── Estado ──────────────────────────────────────────────────────────────────

const slots = useSlots();
const page = usePage<SharedProps>();
const { hasPermission, ensurePermissionsLoaded } =
    useModulePermissions<TablePermissionAction>({
        module: () => props.module,
        permissions: () => props.permissions,
        permissionMap: () => props.permissionMap,
    });

const search = defineModel<SearchValue>('search', { default: '' });
const selectedItems = ref<number[]>([]);
const pagination = ref({ page: props.currentPage, perPage: props.perPage });
const internalLoading = ref(props.loading);
const modalVisibility = ref(false);
const internalVisibilityFilter = ref(
    page.props.filters?.visibility ?? 'visible',
);

// Garante que o campo de busca inicial seja válido; cai no primeiro searchable se não encontrar.
const resolveSearchField = (key?: string): string => {
    const headers = props.headers.filter((h) => h.searchable !== undefined);
    return headers.some((h) => h.key === key) ? key! : (headers[0]?.key ?? '');
};

const internalSearchField = ref(
    resolveSearchField(page.props.filters?.searchField),
);

search.value = page.props.filters?.search ?? '';

// ─── Computeds ───────────────────────────────────────────────────────────────

const searchableHeaders = computed(() =>
    props.headers.filter((h) => h.searchable !== undefined),
);

const searchableFieldOptions = computed(() =>
    searchableHeaders.value.map((h) => ({ title: h.title, value: h.key })),
);

const selectedSearchableHeader = computed(() =>
    searchableHeaders.value.find((h) => h.key === internalSearchField.value),
);

const selectedSearchableConfig = computed<SearchableConfig>(() => {
    const s = selectedSearchableHeader.value?.searchable;
    return s && typeof s === 'object' ? s : { component: 'VTextField' };
});

const selectedSearchComponentName = computed<SearchComponentName>(
    () => selectedSearchableConfig.value.component ?? 'VTextField',
);

const selectedSearchComponent = computed(
    () => searchComponentRegistry[selectedSearchComponentName.value],
);

const selectedSearchComponentProps = computed(() => {
    const name = selectedSearchComponentName.value;
    const label = selectedSearchableHeader.value?.title ?? '';
    const base: Record<string, unknown> = {
        label: `Pesquisar por ${label}`,
        hideDetails: true,
    };

    if (name === 'VTextField' || name === 'MaskedTextField') {
        base.clearable = true;
        base.prependInnerIcon = 'ti ti-search';
    }

    if (name === 'VSelect') {
        base.clearable = true;
    }

    const componentProps = selectedSearchableConfig.value.props;
    const resolvedProps =
        typeof componentProps === 'function'
            ? componentProps(searchInputValue.value)
            : (componentProps ?? {});

    return { ...base, ...resolvedProps };
});

// Normaliza o v-model entre componentes de busca com tipos diferentes.
const searchInputValue = computed<SearchValue>({
    get: () =>
        selectedSearchComponentName.value === 'DateField'
            ? String(search.value ?? '')
            : search.value,
    set: (v) => {
        search.value =
            selectedSearchComponentName.value === 'DateField'
                ? typeof v === 'string'
                    ? v
                    : ''
                : v;
    },
});

const permissions = computed(() => ({
    view: hasPermission('view'),
    create: hasPermission('create'),
    delete: hasPermission('delete'),
    visibility: hasPermission('visibility'),
}));

const hasExtraActions = computed(() => !!slots['extra-actions']);

// Colunas de seleção e ações são injetadas aqui para não poluir as definições de cada página.
const computedHeaders = computed(() => {
    const headers = [...props.headers];

    if (!props.hideSelection) {
        headers.unshift({
            title: '',
            key: 'data-table-select',
            sortable: false,
            align: 'center',
            width: '50px',
        });
    }

    if (permissions.value.view || hasExtraActions.value) {
        headers.push({
            title: 'Ações',
            key: 'actions',
            sortable: false,
            align: 'center',
            width: '100px',
        });
    }

    return headers;
});

// ─── Navegação / Inertia ─────────────────────────────────────────────────────

// Centraliza todas as visitas ao backend; preserva estado visual entre filtros e paginação.
const loadItems = (options?: {
    page?: number;
    sortBy?: { key: string; order: string }[];
}) => {
    if (!props.routes?.index) return;

    internalLoading.value = true;
    emit('reload');

    const params: Record<string, FormDataConvertible> = {
        page: options?.page ?? pagination.value.page,
        per_page: pagination.value.perPage,
        [props.searchKey]: search.value ?? '',
        searchField: internalSearchField.value,
        visibility: internalVisibilityFilter.value,
    };

    if (options?.sortBy?.length) {
        params.sort_by = options.sortBy[0].key;
        params.sort_direction = options.sortBy[0].order;
    }

    router.get(props.routes.index, params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            internalLoading.value = false;
        },
    });
};

// ─── Handlers ────────────────────────────────────────────────────────────────

const handleTableUpdate = (options: {
    page: number;
    itemsPerPage: number;
    sortBy?: any[];
}) => {
    pagination.value.page = options.page;
    pagination.value.perPage = options.itemsPerPage;
    emit('update:page', options.page);
    emit('update:perPage', options.itemsPerPage);
    loadItems(options);
};

const handleEdit = (item: unknown) => {
    if (!permissions.value.view) return;
    emit('edit', item);

    const route = props.routes?.show?.replace(':id', String((item as { id: number }).id));

    if (route) {
        router.get(route);
    }
};

const handleDelete = () => {
    if (!confirm('Tem certeza que deseja excluir estes itens?')) return;

    emit('delete', selectedItems.value);

    const route = props.routes?.destroy ?? null;

    if (route !== null) {
        internalLoading.value = true;
        const payload: Record<string, FormDataConvertible> = {
            items: selectedItems.value,
        };

        router.delete(route, {
            data: payload,
            preserveScroll: true,
            onSuccess: () => loadItems(),
            onFinish: () => {
                internalLoading.value = false;
            },
        });
    }
};

const handleCreate = () => {
    if (!permissions.value.create) return;
    emit('create');

    if (props.routes?.create) {
        router.get(props.routes.create);
    }
};

const changeVisibility = (visibility: string) => {
    if (!permissions.value.visibility) return;
    emit('change:visibility', selectedItems.value, visibility);

    const route = props.routes?.changeVisibility ?? null;

    if (route !== null) {
        router.patch(
            route,
            { items: selectedItems.value, visibility },
            { preserveScroll: true, onSuccess: () => loadItems() },
        );
    }
};

// Limpa seleção e volta à página 1 ao trocar filtro de visibilidade para evitar estado inconsistente.
const applyVisibilityFilter = (visibility: VisibilityValue) => {
    if (internalVisibilityFilter.value === visibility) return;
    internalVisibilityFilter.value = visibility;
    selectedItems.value = [];
    pagination.value.page = 1;
    emit('update:page', 1);
    loadItems({ page: 1 });
};

let skipNextSearchWatch = false;

const handleSearchFieldChange = (field: string | null) => {
    if (!field || field === internalSearchField.value) return;
    internalSearchField.value = field;
    skipNextSearchWatch = true;
    search.value = '';
    pagination.value.page = 1;
    emit('update:page', 1);
    emit('update:search', '');
    loadItems({ page: 1 });
};

const handleSelection = (items: number[]) => {
    selectedItems.value = items;
    emit('selection', items);
};

const handleRowDoubleClick = (
    _event: MouseEvent,
    payload: { item: unknown },
) => {
    handleEdit(payload.item);
};

// ─── Watchers ────────────────────────────────────────────────────────────────

// Busca com debounce para evitar múltiplas visitas Inertia em sequência.
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (skipNextSearchWatch) {
        skipNextSearchWatch = false;
        return;
    }

    if (searchTimeout) clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        pagination.value.page = 1;
        emit('update:page', 1);
        emit('update:search', value ?? '');
        loadItems();
    }, 500);
});

// Sincroniza estado interno com os filtros vindos do backend (ex.: navegação pelo histórico do browser).
watch(
    () => page.props.filters,
    (filters) => {
        search.value = filters?.search ?? '';
        internalSearchField.value = resolveSearchField(filters?.searchField);
        internalVisibilityFilter.value = filters?.visibility ?? 'visible';
    },
    { deep: true },
);

// ─── Lifecycle ───────────────────────────────────────────────────────────────

onMounted(() => {
    void ensurePermissionsLoaded();
});

// Exposição mínima para páginas que precisem disparar reload manual ou inspecionar seleção.
defineExpose({ loadItems, selectedItems, internalLoading });
</script>

<template>
    <div>
        <!-- Modal de visibilidade -->
        <v-dialog v-model="modalVisibility" width="300px">
            <v-card>
                <v-card-title>Alterar Visibilidade</v-card-title>
                <v-list density="compact" nav>
                    <v-list-item
                        class="clipped-object"
                        v-for="item in visibilityOptions"
                        :key="item.value"
                        :prepend-icon="item.icon"
                        :title="item.title"
                        :value="item.value"
                        @click="
                            changeVisibility(item.value);
                            modalVisibility = false;
                        "
                    />
                </v-list>
            </v-card>
        </v-dialog>

        <!-- Barra de ferramentas -->
        <div class="d-flex justify-start align-center flex-wrap ga-3 my-4">
            <!-- O campo principal de busca muda dinamicamente conforme o header configurado. -->
            <component
                :is="selectedSearchComponent"
                v-model="searchInputValue"
                v-bind="selectedSearchComponentProps"
                class="mw-33"
            />
            <v-select
                v-if="searchableFieldOptions.length > 0"
                :model-value="internalSearchField"
                :items="searchableFieldOptions"
                hide-details
                variant="solo-filled"
                :style="{ 'max-width': '180px' }"
                @update:model-value="handleSearchFieldChange"
            />

            <div class="flex-grow-1" />

            <v-clipped-button
                v-if="permissions.create"
                color="primary"
                prepend-icon="ti ti-plus"
                class="ml-2"
                @click="handleCreate"
            >
                Novo
            </v-clipped-button>
            <v-clipped-button
                v-if="permissions.visibility && selectedItems.length > 0"
                color="secondary"
                prepend-icon="ti ti-eye"
                class="ml-2"
                @click="modalVisibility = true"
            >
                Alterar Visibilidade
            </v-clipped-button>
            <v-clipped-button
                v-if="permissions.delete && selectedItems.length > 0 && internalVisibilityFilter=='archived'"
                color="error"
                prepend-icon="ti ti-trash"
                class="ml-2"
                @click="handleDelete"
            >
                Deletar
            </v-clipped-button>
        </div>

        <!-- Filtro de visibilidade -->
        <div class="mb-4">
            <v-btn-group class="bg-secondary" border="0">
                <v-btn
                    v-for="item in visibilityOptions"
                    :key="item.value"
                    :color="
                        internalVisibilityFilter === item.value
                            ? 'primary'
                            : undefined
                    "
                    :variant="
                        internalVisibilityFilter === item.value
                            ? 'flat'
                            : 'text'
                    "
                    :prepend-icon="item.icon"
                    @click="applyVisibilityFilter(item.value)"
                >
                    {{ item.title }}
                </v-btn>
            </v-btn-group>
        </div>

        <!-- Tabela -->
        <v-data-table
            :headers="computedHeaders"
            :items="items"
            :items-per-page="pagination.perPage"
            :page="pagination.page"
            :items-length="total"
            :loading="internalLoading"
            :show-select="!hideSelection"
            :model-value="selectedItems"
            loading-text="Carregando..."
            hover
            class="elevation-1"
            @dblclick:row="handleRowDoubleClick"
            @update:options="handleTableUpdate"
            @update:model-value="handleSelection"
        >
            <!-- Colunas personalizadas -->
            <template
                v-for="slot in customSlots"
                #[`item.${slot}`]="{ item }"
                :key="slot"
            >
                <!-- `column-{slot}` permite sobrescrever apenas a célula necessária. -->
                <slot :name="`column-${slot}`" :item="item" />
            </template>

            <!-- Ações -->
            <template
                v-if="permissions.view || hasExtraActions"
                #item.actions="{ item }"
            >
                <div class="d-flex gap-1">
                    <v-btn-icon
                        v-if="permissions.view"
                        icon="ti ti-pencil"
                        size="small"
                        @click="handleEdit(item)"
                    />
                    <slot name="extra-actions" :item="item" />
                </div>
            </template>

            <!-- Estado vazio -->
            <template #no-data>
                <div class="text-center pa-4">
                    <v-icon
                        icon="ti ti-database-off"
                        size="large"
                        color="grey-lighten-1"
                    />
                    <p class="text-body-1 mt-2">Nenhum item encontrado</p>
                    <v-btn
                        v-if="search"
                        color="primary"
                        variant="text"
                        class="mt-2"
                        @click="search = ''"
                    >
                        Limpar busca
                    </v-btn>
                </div>
            </template>

            <!-- Loading -->
            <template #loading>
                <div class="text-center pa-4">
                    <v-progress-circular indeterminate color="primary" />
                    <p class="mt-2">Carregando dados...</p>
                </div>
            </template>

            <!-- Rodapé com paginação -->
            <template #bottom>
                <div class="d-flex justify-space-between align-center pa-2">
                    <span class="text-caption text-grey"
                        >Total: {{ total }} item(s)</span
                    >
                    <v-pagination
                        v-model="pagination.page"
                        :length="lastPage"
                        :total-visible="7"
                        @update:model-value="loadItems({ page: $event })"
                    />
                </div>
            </template>
        </v-data-table>
    </div>
</template>
