<!-- resources/js/Components/GenericTable.vue -->
<script setup lang="ts">
import { computed, onMounted, ref, useSlots, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';

type TablePermissionAction = 'view' | 'create' | 'delete' | 'visibility';

// Props genéricos
interface Props {
    // Dados da tabela
    items: any[];
    total: number;
    currentPage: number;
    lastPage: number;
    perPage: number;

    // Configuração da tabela
    headers: Array<{
        title: string;
        key: string;
        sortable?: boolean;
        align?: 'start' | 'center' | 'end';
        width?: string;
    }>;

    routes?: {
        index: string;
        create?: string;
        changeVisibility?: (items: any[], visibility: string) => string;
        show?: (id: number) => string;
        destroy?: (items: any[]) => string;
    };

    hideSelection?: boolean;
    loading?: boolean;
    searchKey?: string;
    title?: string;
    module?: string;
    permissions?: string[];
    permissionMap?: Partial<Record<TablePermissionAction, string | false>>;

    // Slots para personalização
    customSlots?: string[];
}

export interface TableHeader {
    title: string;
    key: string;
    sortable?: boolean;
    align?: 'start' | 'center' | 'end';
    width?: string;
}

export interface TableRoutes {
    index: string;
    create?: string;
    changeVisibility?: (items: any[], visibility: string) => string;
    show?: (id: number) => string;
    destroy?: (items: any[]) => string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

type SharedProps = {
    filters?: {
        visibility?: string;
    };
};

const props = withDefaults(defineProps<Props>(), {
    hideSelection: false,
    loading: false,
    searchKey: 'search',
    title: 'Items',
    module: undefined,
    permissions: undefined,
    permissionMap: undefined,
    customSlots: () => [],
});

const slots = useSlots();
const page = usePage<SharedProps>();
const { permissions: loadedPermissions, loadPermissions } = usePermissions();

// Emits
const emit = defineEmits<{
    'update:search': [value: string];
    'update:page': [value: number];
    'update:perPage': [value: number];
    edit: [item: any];
    delete: [items: any[]];
    'change:visibility': [items: any[], visibility: string];
    create: [];
    selection: [items: any[]];
    reload: [];
}>();

// Estado interno
const search = defineModel<string>('search', { default: '' });
const selectedItems = ref<any[]>([]);
const internalPage = ref(props.currentPage);
const internalPerPage = ref(props.perPage);
const internalLoading = ref(props.loading);
const modalVisibility = ref(false);
const internalVisibilityFilter = ref(
    page.props.filters?.visibility ?? 'visible',
);
const internalVisibilityOptions = ref<any[]>([
    { title: 'Visível', value: 'visible', icon: 'ti ti-eye' },
    { title: 'Oculto', value: 'hidden', icon: 'ti ti-eye-off' },
    { title: 'Arquivado', value: 'archived', icon: 'ti ti-archive' },
]);

let searchTimeout: number | null = null;

const permissionSource = computed(() => {
    return props.permissions ?? loadedPermissions.value;
});

const usesModulePermissions = computed(() => {
    return props.module !== undefined || props.permissionMap !== undefined;
});

const hasExtraActions = computed(() => {
    return slots['extra-actions'] !== undefined;
});

const resolvePermission = (action: TablePermissionAction): string | null => {
    const override = props.permissionMap?.[action];

    if (override === false) {
        return null;
    }

    if (typeof override === 'string') {
        return override;
    }

    if (props.module === undefined) {
        return null;
    }

    return `${props.module}.${action}`;
};

const hasPermission = (action: TablePermissionAction): boolean => {
    const permission = resolvePermission(action);

    if (permission === null) {
        return true;
    }

    return permissionSource.value.includes(permission);
};

const canOpenDetails = computed(() => hasPermission('view'));
const canCreate = computed(() => hasPermission('create'));
const canDelete = computed(() => hasPermission('delete'));
const canChangeVisibility = computed(() => hasPermission('visibility'));

// Headers com ações
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

    if (canOpenDetails.value || hasExtraActions.value) {
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

const isSelectable = computed(() => {
    return !props.hideSelection && selectedItems.value.length > 0;
});

const selectedIds = computed(() => {
    return selectedItems.value.map((item) => item.id);
});

// Métodos públicos
const loadItems = (options?: { page?: number; sortBy?: any[] }) => {
    if (!props.routes?.index) return;

    internalLoading.value = true;
    emit('reload');

    const params: any = {
        page: options?.page || internalPage.value,
        per_page: internalPerPage.value,
        [props.searchKey]: search.value,
        visibility: internalVisibilityFilter.value,
    };

    if (options?.sortBy && options.sortBy.length > 0) {
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

const handleTableUpdate = (options: any) => {
    internalPage.value = options.page;
    internalPerPage.value = options.itemsPerPage;
    emit('update:page', options.page);
    emit('update:perPage', options.itemsPerPage);
    loadItems(options);
};

const handleEdit = (item: any) => {
    if (!canOpenDetails.value) {
        return;
    }

    emit('edit', item);

    if (props.routes?.show) {
        router.get(props.routes.show(item.id));
    }
};

const handleDelete = () => {
    if (confirm(`Tem certeza que deseja excluir estes itens?`)) {
        emit('delete', selectedIds.value);
        if (props.routes?.destroy) {
            router.delete(props.routes.destroy(selectedIds.value), {
                preserveScroll: true,
                onSuccess: () => loadItems(),
            });
        }
    }
};

const handleCreate = () => {
    if (!canCreate.value) {
        return;
    }

    emit('create');
    if (props.routes?.create) {
        router.get(props.routes.create);
    }
};

const changeVisibility = (visibility: string) => {
    if (!canChangeVisibility.value) {
        return;
    }

    emit('change:visibility', selectedIds.value, visibility);
    if (props.routes?.changeVisibility) {
        router.post(
            props.routes.changeVisibility(selectedIds.value, visibility),
            { items: selectedIds.value, visibility },
            {
                preserveScroll: true,
                onSuccess: () => loadItems(),
            },
        );
    }
};

const handleSelection = (items: any[]) => {
    selectedItems.value = items;
    emit('selection', items);
};

const applyVisibilityFilter = (visibility: string) => {
    if (internalVisibilityFilter.value === visibility) {
        return;
    }

    internalVisibilityFilter.value = visibility;
    selectedItems.value = [];
    internalPage.value = 1;
    emit('update:page', 1);
    loadItems({ page: 1 });
};

const handleRowDoubleClick = (_event: MouseEvent, payload: { item: any }) => {
    handleEdit(payload.item);
};

onMounted(() => {
    if (props.permissions === undefined && usesModulePermissions.value) {
        void loadPermissions();
    }
});

// Watch para busca
watch(search, (newValue) => {
    if (searchTimeout) clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        internalPage.value = 1;
        emit('update:page', 1);
        emit('update:search', newValue);
        loadItems();
    }, 500);
});

watch(
    () => page.props.filters?.visibility,
    (visibility) => {
        internalVisibilityFilter.value = visibility ?? 'visible';
    },
);

// Expor métodos para o componente pai
defineExpose({
    loadItems,
    selectedItems,
    internalLoading,
});
</script>

<template>
    <div>
        <v-dialog v-model="modalVisibility" width="300px">
            <v-card>
                <v-card-title>Alterar Visibilidade</v-card-title>
                <v-list density="compact" nav>
                    <v-list-item
                        v-for="item in internalVisibilityOptions"
                        :key="item.value"
                        :prepend-icon="item.icon"
                        :title="item.title"
                        :value="item.value"
                        @click="changeVisibility(item.value)"
                    >
                    </v-list-item>
                </v-list>
            </v-card>
        </v-dialog>
        <!-- Cabeçalho -->
        <div class="d-flex justify-start align-center my-4">
            <!-- Busca -->
            <v-text-field
                v-model="search"
                label="Buscar..."
                prepend-inner-icon="ti ti-search"
                clearable
                class="mw-33"
                hide-details
            />
            <div class="flex-grow-1"></div>
            <!-- Botão criar -->
            <v-btn
                v-if="canCreate"
                color="primary"
                prepend-icon="ti ti-plus"
                class="ml-2"
                @click="handleCreate"
            >
                Novo
            </v-btn>
            <!-- Botão criar -->
            <v-btn
                v-if="canChangeVisibility && selectedItems.length > 0"
                color="secondary"
                class="ml-2"
                prepend-icon="ti ti-eye"
                @click="modalVisibility = true"
            >
                Alterar Visibilidade
            </v-btn>
            <v-btn
                v-if="canDelete && selectedItems.length > 0"
                color="secondary"
                class="ml-2"
                prepend-icon="ti ti-eye"
                @click="handleDelete"
            >
                Deletar
            </v-btn>
        </div>

        <div class="mb-4">
            <v-btn-group>
                <v-btn
                    v-for="item in internalVisibilityOptions"
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
            :items-per-page="internalPerPage"
            :page="internalPage"
            :items-length="total"
            :loading="internalLoading"
            :show-select="isSelectable"
            :model-value="selectedItems"
            loading-text="Carregando..."
            hover
            @dblclick:row="handleRowDoubleClick"
            class="elevation-1"
            @update:options="handleTableUpdate"
            @update:model-value="handleSelection"
        >
            <!-- Slot para colunas personalizadas -->
            <template
                v-for="slot in customSlots"
                #[`item.${slot}`]="{ item }"
                :key="slot"
            >
                <slot :name="`column-${slot}`" :item="item" />
            </template>

            <!-- Slot para ações -->
            <template
                v-if="canOpenDetails || hasExtraActions"
                #item.actions="{ item }"
            >
                <div class="d-flex gap-1">
                    <v-btn
                        v-if="canOpenDetails"
                        icon="ti ti-pencil"
                        size="small"
                        color="primary"
                        variant="tonal"
                        @click="handleEdit(item)"
                    />
                    <!-- Slot adicional para ações extras -->
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
                        @click="search = ''"
                        class="mt-2"
                    >
                        Limpar busca
                    </v-btn>
                </div>
            </template>

            <!-- Loading personalizado -->
            <template #loading>
                <div class="text-center pa-4">
                    <v-progress-circular indeterminate color="primary" />
                    <p class="mt-2">Carregando dados...</p>
                </div>
            </template>

            <!-- Footer com informação de total -->
            <template #bottom>
                <div class="d-flex justify-space-between align-center pa-2">
                    <div class="text-caption text-grey">
                        Total: {{ total }} item(s)
                    </div>
                    <v-pagination
                        v-model="internalPage"
                        :length="lastPage"
                        :total-visible="7"
                        @update:model-value="loadItems({ page: $event })"
                    />
                </div>
            </template>
        </v-data-table>
    </div>
</template>
