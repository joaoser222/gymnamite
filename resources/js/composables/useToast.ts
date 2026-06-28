import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export type ToastPayload = {
    id?: number;
    type?: ToastType;
    message: string;
};

const toasts = ref<ToastPayload[]>([]);
let nextToastId = 1;

export function useToast() {
    function show(payload: ToastPayload): void {
        toasts.value.push({
            id: nextToastId++,
            type: payload.type ?? 'info',
            message: payload.message,
        });
    }

    function remove(id: number): void {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }

    function shift(): ToastPayload | null {
        return toasts.value.shift() ?? null;
    }

    return {
        toasts,
        show,
        remove,
        shift,
    };
}
