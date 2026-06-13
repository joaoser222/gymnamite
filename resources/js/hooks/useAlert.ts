// hooks/useAlert.ts
import { openContextModal } from '@mantine/modals';
import { useCallback } from 'react';

interface AlertOptions {
    title?: string;
    message: string;
    type?: 'info' | 'success' | 'error' | 'warning';
    confirmLabel?: string;
    cancelLabel?: string;
    onConfirm?: () => void;
    onCancel?: () => void;
    showCancel?: boolean;
}

interface AlertHook {
    showAlert: (options: AlertOptions) => void;
    showError: (message: string, title?: string) => void;
    showSuccess: (message: string, title?: string) => void;
    showWarning: (message: string, title?: string) => void;
    showInfo: (message: string, title?: string) => void;
    showConfirm: (options: AlertOptions) => void;
}

export function useAlert(): AlertHook {
    const showAlert = useCallback((options: AlertOptions) => {
        const {
            title,
            message,
            type = 'info',
            confirmLabel = 'OK',
            cancelLabel = 'Cancelar',
            onConfirm,
            onCancel,
            showCancel = false,
        } = options;

        openContextModal({
            modal: 'globalAlert',
            title: title || getDefaultTitle(type),
            innerProps: {
                message,
                type,
                confirmLabel,
                cancelLabel,
                onConfirm,
                onCancel,
                showCancel,
            },
        });
    }, []);

    const showError = useCallback((message: string, title?: string) => {
        showAlert({ message, title, type: 'error' });
    }, [showAlert]);

    const showSuccess = useCallback((message: string, title?: string) => {
        showAlert({ message, title, type: 'success' });
    }, [showAlert]);

    const showWarning = useCallback((message: string, title?: string) => {
        showAlert({ message, title, type: 'warning' });
    }, [showAlert]);

    const showInfo = useCallback((message: string, title?: string) => {
        showAlert({ message, title, type: 'info' });
    }, [showAlert]);

    const showConfirm = useCallback((options: AlertOptions) => {
        showAlert({ ...options, showCancel: true });
    }, [showAlert]);

    return {
        showAlert,
        showError,
        showSuccess,
        showWarning,
        showInfo,
        showConfirm,
    };
}

// Helper para títulos padrão
function getDefaultTitle(type: string): string {
    const titles = {
        error: 'Erro!',
        success: 'Sucesso!',
        warning: 'Atenção!',
        info: 'Aviso',
    };

    return titles[type as keyof typeof titles] || 'Aviso';
}
