<template>
    <div class="global-toast-stack">
        <v-snackbar
            v-for="(toast, index) in visibleToasts"
            :key="toast.id"
            :model-value="true"
            :color="toastColor(toast.type ?? 'info')"
            :timeout="timeout"
            variant="flat"
            class="global-toast-item"
            :style="{ top: `${16 + index * 72}px` }"
            @update:model-value="removeToast(toast.id)"
        >
            <div class="d-flex align-center ga-3">
                <v-icon :icon="toastIcon(toast.type ?? 'info')" />
                <span>{{ toast.message }}</span>
            </div>

            <template #actions>
                <v-btn
                    icon="ti ti-x"
                    variant="text"
                    size="small"
                    @click="removeToast(toast.id)"
                />
            </template>
        </v-snackbar>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast, type ToastPayload, type ToastType } from '@/composables/useToast';

const maxVisibleToasts = 3;
const timeout = 4000;
const page = usePage<{
    flash?: {
        toast?: ToastPayload | null;
    };
}>();

const { remove, shift, show, toasts } = useToast();
const visibleToasts = ref<ToastPayload[]>([]);

function toastColor(type: ToastType): string {
    return {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'primary',
    }[type];
}

function toastIcon(type: ToastType): string {
    return {
        success: 'ti ti-check',
        error: 'ti ti-alert-circle',
        warning: 'ti ti-alert-triangle',
        info: 'ti ti-info-circle',
    }[type];
}

function removeToast(id?: number): void {
    if (!id) {
        return;
    }

    visibleToasts.value = visibleToasts.value.filter((toast) => toast.id !== id);
    drainQueue();
}

function drainQueue(): void {
    while (visibleToasts.value.length < maxVisibleToasts) {
        const nextToast = shift();

        if (!nextToast) {
            break;
        }

        visibleToasts.value.push(nextToast);
    }
}

watch(
    () => page.props.flash?.toast,
    (toast) => {
        if (toast?.message) {
            show(toast);
        }
    },
    { immediate: true },
);

watch(
    toasts,
    () => {
        drainQueue();
    },
    { deep: true, immediate: true },
);
</script>

<style scoped>
.global-toast-stack {
    position: fixed;
    top: 0;
    right: 0;
    z-index: 3000;
    pointer-events: none;
}

.global-toast-item {
    position: fixed;
    right: 16px;
    pointer-events: auto;
}
</style>
