// hooks/useLoading.ts
import { openContextModal, closeModal } from '@mantine/modals';
import { useState, useCallback } from 'react';

interface LoadingOptions {
  message?: string;
  subMessage?: string;
  indeterminate?: boolean;
  showProgress?: boolean;
}

interface WithLoadingOptions extends LoadingOptions {
  autoClose?: boolean;
  autoCloseDelay?: number;
}

export function useLoading() {
  const [currentModalId, setCurrentModalId] = useState<string | null>(null);

  const showLoading = useCallback((options: LoadingOptions = {}) => {
    const id = openContextModal({
      modal: 'loadingModal',
      title: '',
      withCloseButton: false,
      closeOnClickOutside: false,
      closeOnEscape: false,
      centered: true,
      size: 'sm',
      innerProps: {
        message: options.message || 'Carregando',
        subMessage: options.subMessage,
        indeterminate: true,
        status: 'loading',
        showProgress: options.showProgress || false,
      },
    });
    
    setCurrentModalId(id as string);
    return id;
  }, []);

  const updateLoading = useCallback((modalId: string, updates: any) => {
    // Nota: Mantine não tem update nativo, então recriamos o modal
    // Fecha o atual
    closeModal(modalId);
    
    // Abre com novos dados
    const newId = openContextModal({
      modal: 'loadingModal',
      title: '',
      withCloseButton: false,
      closeOnClickOutside: false,
      closeOnEscape: false,
      centered: true,
      size: 'sm',
      innerProps: updates,
    });
    
    setCurrentModalId(newId as string);
    return newId;
  }, []);

  const closeLoading = useCallback((modalId?: string) => {
    const idToClose = modalId || currentModalId;
    if (idToClose) {
      closeModal(idToClose);
      setCurrentModalId(null);
    }
  }, [currentModalId]);

  const withLoading = useCallback(async <T,>(
    promise: Promise<T> | (() => Promise<T>),
    options: WithLoadingOptions = {}
  ): Promise<T> => {
    const {
      autoClose = false,
      autoCloseDelay = 1500,
      ...loadingOptions
    } = options;

    let modalId: string | null = null;
    
    try {
      // Mostra loading
      modalId = showLoading(loadingOptions) as string;
      
      // Executa a promise
      const result = await (typeof promise === 'function' ? promise() : promise);
      
      // Se chegou aqui, sucesso - fecha o modal (opcionalmente após delay)
      if (autoClose) {
        // Mostra mensagem de sucesso rapidamente
        const successId = updateLoading(modalId, {
          title: 'Concluído!',
          status: 'success',
          message: 'Operação realizada com sucesso',
        });
        
        // Fecha após o delay
        setTimeout(() => {
          closeLoading(successId as string);
        }, autoCloseDelay);
      } else {
        closeLoading(modalId);
      }
      
      return result;
      
    } catch (error) {
      console.error('Erro na operação:', error);

      if (modalId) {
        closeLoading(modalId);
      }

      throw error;
    }
  }, [showLoading, updateLoading, closeLoading]);

  return {
    showLoading,
    updateLoading,
    closeLoading,
    withLoading,
  };
}