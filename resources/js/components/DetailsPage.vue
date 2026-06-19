<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

type DetailsPermissionAction = 'create' | 'update';

export interface DetailsRoutes {
    index: string;
    store: string;
    update: string;
}

type FormData = Record<string, any>;
type VForm = {
    validate: () => Promise<{ valid: boolean }>;
    resetValidation: () => void;
};

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
        permissionMap?: Partial<
            Record<DetailsPermissionAction, string | false>
        >;
    }>(),
    {
        item: null,
        defaults: () => ({}),
        itemKey: 'id',
        saveLabel: 'Salvar',
        cancelLabel: 'Cancelar',
        module: undefined,
        permissions: undefined,
        permissionMap: undefined,
    },
);

const formDetails = ref<VForm | null>(null);
const isValid = ref<boolean | null>(null);
const hasValidated = ref(false);
const { permissions: loadedPermissions, loadPermissions } = usePermissions();

const initialData = computed<FormData>(() => ({
    ...props.defaults,
    ...(props.item ?? {}),
}));

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
const permissionSource = computed(() => {
    return props.permissions ?? loadedPermissions.value;
});

const usesModulePermissions = computed(() => {
    return props.module !== undefined || props.permissionMap !== undefined;
});

const resolvePermission = (action: DetailsPermissionAction): string | null => {
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

const hasPermission = (action: DetailsPermissionAction): boolean => {
    const permission = resolvePermission(action);

    if (permission === null) {
        return true;
    }

    return permissionSource.value.includes(permission);
};

const canSubmitPermission = computed(() => {
    return isCreating.value ? hasPermission('create') : hasPermission('update');
});

const canSave = computed(
    () =>
        canSubmitPermission.value &&
        hasValidated.value &&
        isValid.value === true &&
        !form.processing,
);

const routeWithId = (route: string): string => {
    if (recordId.value === undefined || recordId.value === null) {
        return route;
    }

    return route.replace(':id', String(recordId.value));
};

const validate = async (): Promise<boolean> => {
    if (!formDetails.value) {
        hasValidated.value = false;
        isValid.value = false;

        return false;
    }

    const result = await formDetails.value.validate();

    hasValidated.value = true;
    isValid.value = result.valid;

    return result.valid;
};

const submit = async (): Promise<void> => {
    if (!canSubmitPermission.value) {
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
        form.post(props.routes.store, options);

        return;
    }

    form.put(routeWithId(props.routes.update), options);
};

const cancel = (): void => {
    emit('cancel');
    router.visit(props.routes.index);
};

watch(
    initialData,
    (data) => {
        form.defaults({ ...data });
        form.reset();
        formDetails.value?.resetValidation();
        hasValidated.value = false;
        isValid.value = null;
        void nextTick(validate);
    },
    { deep: true },
);

watch(
    form,
    () => {
        void nextTick(validate);
    },
    { deep: true },
);

onMounted(() => {
    if (props.permissions === undefined && usesModulePermissions.value) {
        void loadPermissions();
    }
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
                    v-model="isValid"
                    validate-on="input"
                    @submit.prevent="submit"
                >
                    <slot
                        :form="form"
                        :errors="form.errors"
                        :is-creating="isCreating"
                        :validate="validate"
                        :canSubmit="canSubmitPermission"
                        :readonly="!canSubmitPermission"
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
                v-if="canSubmitPermission"
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
