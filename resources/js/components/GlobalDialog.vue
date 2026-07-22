<template>
    <v-dialog v-model="visible" max-width="460">
        <v-card>
            <v-card-title class="d-flex align-center ga-2">
                <v-icon :icon="dialogIcon" :color="dialogColor" />
                {{ dialog?.title ?? 'Aviso' }}
            </v-card-title>
            <v-card-text>
                {{ dialog?.message }}
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn :color="dialogColor" variant="flat" @click="visible = false">
                    Entendi
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

type DialogType = 'error' | 'warning' | 'info' | 'success';

type DialogPayload = {
    type?: DialogType;
    title?: string;
    message?: string;
};

const page = usePage<{
    flash?: {
        dialog?: DialogPayload | null;
    };
}>();

const visible = ref(false);
const dialog = ref<DialogPayload | null>(null);

const dialogColor = computed(() => ({
    error: 'error',
    warning: 'warning',
    info: 'primary',
    success: 'success',
}[dialog.value?.type ?? 'info']));

const dialogIcon = computed(() => ({
    error: 'ti ti-alert-circle',
    warning: 'ti ti-alert-triangle',
    info: 'ti ti-info-circle',
    success: 'ti ti-check',
}[dialog.value?.type ?? 'info']));

watch(
    () => page.props.flash?.dialog,
    (payload) => {
        if (!payload?.message) {
            return;
        }

        dialog.value = payload;
        visible.value = true;
    },
    { immediate: true },
);
</script>
