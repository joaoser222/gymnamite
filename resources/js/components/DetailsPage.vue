<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import {
    useModulePermissions,
    type ModulePermissionMap,
} from '@/composables/useModulePermissions';
import type { DetailsRoutes } from '@/shared/page';

// O formulário genérico diferencia automaticamente permissões de criação e atualização.
type DetailsPermissionAction = 'create' | 'update';
type DetailsPermissionMap = ModulePermissionMap<DetailsPermissionAction>;

type FormData = Record<string, any>;
type VForm = {
    validate: () => Promise<{ valid: boolean }>;
    resetValidation: () => void;
};

// A página dona do formulário fornece dados, rotas e defaults; este componente cuida do ciclo de edição.
const props = withDefaults(
    defineProps<{
        title: string;
        item?: FormData | null;
        defaults?: FormData;
        routes: DetailsRoutes;
        itemKey?: string;
        saveLabel?: string;
        cancelLabel?: string;
        module?: string;
        permissions?: string[];
        permissionMap?: DetailsPermissionMap;
    }>(),
    {
        item: null,
        defaults: () => ({}),
        itemKey: 'id',
        saveLabel: 'Salvar',
        cancelLabel: 'Cancelar',
    },
);

const formDetails = ref<VForm | null>(null);
const formState = ref({ valid: null as boolean | null, validated: false });
const { hasPermission, ensurePermissionsLoaded } =
    useModulePermissions<DetailsPermissionAction>({
        module: () => props.module,
        permissions: () => props.permissions,
        permissionMap: () => props.permissionMap,
    });

const initialData = computed<FormData>(() => ({
    ...props.defaults,
    ...(props.item ?? {}),
}));

// `useForm` mantém integração com validação/erros do Inertia sem acoplar o schema ao componente genérico.
const form = useForm<FormData>({ ...initialData.value });

const emit = defineEmits<{
    save: [form: typeof form];
    cancel: [];
}>();

const recordId = computed(() => props.item?.[props.itemKey]);
const isCreating = computed(
    () => recordId.value === undefined || recordId.value === null,
);
const pageTitle = computed(
    () => `${isCreating.value ? 'Novo' : 'Editar'} ${props.title}`,
);

const permissions = computed(() => ({
    submit: isCreating.value ? hasPermission('create') : hasPermission('update'),
}));

const canSave = computed(
    () =>
        permissions.value.submit &&
        formState.value.validated &&
        formState.value.valid === true &&
        !form.processing,
);

const validate = async (): Promise<boolean> => {
    if (!formDetails.value) {
        formState.value.validated = false;
        formState.value.valid = false;

        return false;
    }

    const result = await formDetails.value.validate();

    formState.value.validated = true;
    formState.value.valid = result.valid;

    return result.valid;
};

// O submit escolhe automaticamente entre criação e atualização a partir da presença do identificador.
const submit = async (): Promise<void> => {
    if (!permissions.value.submit) {
        return;
    }

    if (!(await validate())) {
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('save', form),
    };

    if (isCreating.value) {
        if (props.routes.store) {
            form.post(props.routes.store, options);
        }

        return;
    }

    const updateRoute = props.routes.update?.replace(':id', String(recordId.value));

    if (updateRoute) {
        form.put(updateRoute, options);
    }
};

const cancel = (): void => {
    emit('cancel');

    if (props.routes.index) {
        router.visit(props.routes.index);
    }
};

watch(
    initialData,
    (data) => {
        // Sempre que o item muda, o formulário volta ao estado base daquela edição.
        form.defaults({ ...data });
        form.reset();
        formDetails.value?.resetValidation();
        formState.value.validated = false;
        formState.value.valid = null;
        void nextTick(validate);
    },
    { deep: true },
);

watch(
    form,
    () => {
        // Revalida de forma assíncrona para manter o estado do botão consistente com o formulário atual.
        void nextTick(validate);
    },
    { deep: true },
);

onMounted(() => {
    void ensurePermissionsLoaded();
});

void nextTick(validate);
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between ga-4 my-4">
            <div>
                <h1 class="text-h5 font-weight-medium">{{ pageTitle }}</h1>
            </div>
        </div>

        <v-card>
            <v-card-text>
                <v-form
                    ref="formDetails"
                    v-model="formState.valid"
                    validate-on="input"
                    @submit.prevent="submit"
                >
                    <slot
                        :form="form"
                        :errors="form.errors"
                        :is-creating="isCreating"
                        :validate="validate"
                        :canSubmit="permissions.submit"
                        :readonly="!permissions.submit"
                    />
                </v-form>
            </v-card-text>
        </v-card>

        <div class="d-flex ga-2 pa-3 justify-end">
            <v-btn
                color="secondary"
                prepend-icon="ti ti-arrow-left"
                :disabled="form.processing"
                @click="cancel"
            >
                {{ cancelLabel }}
            </v-btn>
            <v-btn
                v-if="permissions.submit"
                color="primary"
                prepend-icon="ti ti-device-floppy"
                :loading="form.processing"
                :disabled="!canSave"
                @click="submit"
            >
                {{ saveLabel }}
            </v-btn>
        </div>
    </div>
</template>
