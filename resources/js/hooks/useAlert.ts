// hooks/useAlert.ts
import { openContextModal } from '@mantine/modals';
import { ReactNode } from 'react';

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
  const showAlert = (options: AlertOptions) => {
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
  };

  const showError = (message: string, title?: string) => {
    showAlert({ message, title, type: 'error' });
  };

  const showSuccess = (message: string, title?: string) => {
    showAlert({ message, title, type: 'success' });
  };

  const showWarning = (message: string, title?: string) => {
    showAlert({ message, title, type: 'warning' });
  };

  const showInfo = (message: string, title?: string) => {
    showAlert({ message, title, type: 'info' });
  };

  const showConfirm = (options: AlertOptions) => {
    showAlert({ ...options, showCancel: true });
  };

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