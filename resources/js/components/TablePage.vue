<!-- resources/js/Components/GenericTable.vue -->
<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

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
        edit?: (id: number) => string;
        destroy?: (id: number) => string;
    };
    
    // Configurações adicionais
    searchable?: boolean;
    creatable?: boolean;
    editable?: boolean;
    deletable?: boolean;
    selectable?: boolean;
    loading?: boolean;
    searchKey?: string;
    title?: string;
    
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
    edit?: (id: number) => string;
    destroy?: (id: number) => string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = withDefaults(defineProps<Props>(), {
    searchable: true,
    creatable: true,
    editable: true,
    deletable: true,
    selectable: false,
    loading: false,
    searchKey: 'search',
    title: 'Items',
    customSlots: () => []
});

// Emits
const emit = defineEmits<{
    'update:search': [value: string];
    'update:page': [value: number];
    'update:perPage': [value: number];
    'edit': [item: any];
    'delete': [item: any];
    'create': [];
    'selection': [items: any[]];
    'reload': [];
}>();

// Estado interno
const search = defineModel<string>('search', { default: '' });
const selectedItems = ref<any[]>([]);
const internalPage = ref(props.currentPage);
const internalPerPage = ref(props.perPage);
const internalLoading = ref(props.loading);

let searchTimeout: number | null = null;

// Headers com ações
const computedHeaders = computed(() => {
    const headers = [...props.headers];
    
    if (props.selectable) {
        headers.unshift({
            title: '',
            key: 'data-table-select',
            sortable: false,
            align: 'center',
            width: '50px'
        });
    }
    
    if (props.editable || props.deletable) {
        headers.push({
            title: 'Ações',
            key: 'actions',
            sortable: false,
            align: 'center',
            width: '100px'
        });
    }
    
    return headers;
});

// Métodos públicos
const loadItems = (options?: { page?: number; sortBy?: any[] }) => {
    if (!props.routes?.index) return;
    
    internalLoading.value = true;
    emit('reload');
    
    const params: any = {
        page: options?.page || internalPage.value,
        per_page: internalPerPage.value,
        [props.searchKey]: search.value
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
        }
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
    emit('edit', item);
    if (props.routes?.edit) {
        router.get(props.routes.edit(item.id));
    }
};

const handleDelete = (item: any) => {
    if (confirm(`Tem certeza que deseja excluir este item?`)) {
        emit('delete', item);
        if (props.routes?.destroy) {
            router.delete(props.routes.destroy(item.id), {
                preserveScroll: true,
                onSuccess: () => loadItems()
            });
        }
    }
};

const handleCreate = () => {
    emit('create');
    if (props.routes?.create) {
        router.get(props.routes.create);
    }
};

const handleSelection = (items: any[]) => {
    selectedItems.value = items;
    emit('selection', items);
};

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

// Expor métodos para o componente pai
defineExpose({
    loadItems,
    selectedItems,
    internalLoading
});
</script>

<template>
    <div>
        <!-- Cabeçalho -->
        <div class="d-flex justify-space-between align-center mb-4 flex-wrap gap-2 ">
            <div class="d-flex gap-2 py-4 w-50">
                <!-- Busca -->
                <v-text-field
                    v-if="searchable"
                    v-model="search"
                    label="Buscar..."
                    prepend-inner-icon="ti ti-search"
                    clearable
                    hide-details
                    class="search-field"
                />
                
                <!-- Botão criar -->
                <v-btn
                    v-if="creatable"
                    color="primary"
                    prepend-icon="ti ti-plus"
                    @click="handleCreate"
                >
                    Novo
                </v-btn>
            </div>
        </div>
        
        <!-- Tabela -->
        <v-data-table
            :headers="computedHeaders"
            :items="items"
            :items-per-page="internalPerPage"
            :page="internalPage"
            :items-length="total"
            :loading="internalLoading"
            :show-select="selectable"
            :model-value="selectedItems"
            loading-text="Carregando..."
            hover
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
            <template v-if="editable || deletable" #item.actions="{ item }">
                <div class="d-flex gap-1">
                    <v-btn
                        v-if="editable"
                        icon="ti ti-pencil"
                        size="small"
                        color="primary"
                        variant="tonal"
                        @click="handleEdit(item)"
                    />
                    <v-btn
                        v-if="deletable"
                        icon="ti ti-trash"
                        size="small"
                        color="error"
                        variant="tonal"
                        @click="handleDelete(item)"
                    />
                    <!-- Slot adicional para ações extras -->
                    <slot name="extra-actions" :item="item" />
                </div>
            </template>
            
            <!-- Estado vazio -->
            <template #no-data>
                <div class="text-center pa-4">
                    <v-icon icon="ti ti-database-off" size="large" color="grey-lighten-1" />
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
                        @update:model-value="loadItems"
                    />
                </div>
            </template>
        </v-data-table>
        
        <!-- Ações em massa -->
        <v-slide-y-transition>
            <v-card
                v-if="selectable && selectedItems.length > 0"
                class="mt-4"
                color="primary"
                variant="tonal"
            >
                <div class="d-flex justify-space-between align-center pa-3">
                    <span>
                        <strong>{{ selectedItems.length }}</strong> item(s) selecionado(s)
                    </span>
                    <slot name="bulk-actions" :selected="selectedItems" />
                </div>
            </v-card>
        </v-slide-y-transition>
    </div>
</template>

<style scoped>
.gap-2 {
    gap: 8px;
}
.gap-1 {
    gap: 4px;
}
.search-field {
    max-width: 300px;
}
@media (max-width: 600px) {
    .search-field {
        max-width: 100%;
    }
}
</style>