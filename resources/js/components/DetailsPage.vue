<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

export interface DetailsRoutes {
    index: string;
    store: string;
    update: string;
}

type FormData = Record<string, unknown>;
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
    }>(),
    {
        item: null,
        defaults: () => ({}),
        itemKey: 'id',
        saveLabel: 'Salvar',
        cancelLabel: 'Cancelar',
    },
);

const emit = defineEmits<{
    save: [form: ReturnType<typeof useForm<FormData>>];
    cancel: [];
}>();

const formDetails = ref<VForm | null>(null);
const isValid = ref<boolean | null>(null);
const hasValidated = ref(false);

const initialData = computed<FormData>(() => ({
    ...props.defaults,
    ...(props.item ?? {}),
}));

const form = useForm<FormData>({ ...initialData.value });

const recordId = computed(() => props.item?.[props.itemKey]);
const isCreating = computed(
    () => recordId.value === undefined || recordId.value === null,
);
const pageTitle = computed(
    () => `${isCreating.value ? 'Novo' : 'Editar'} ${props.title}`,
);
const canSave = computed(
    () => hasValidated.value && isValid.value === true && !form.processing,
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
