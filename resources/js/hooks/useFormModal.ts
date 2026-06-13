// hooks/useFormModal.ts
import { useDisclosure } from '@mantine/hooks';
import { useState } from 'react';

type FormMode = 'create' | 'edit';

export interface FormModalProps<T = any> {
    opened: boolean;
    mode: FormMode;
    editValues?: Partial<T> | null;
    onClose: () => void;
    onSubmit: (values: T) => Promise<void>;
}

export function useFormModal<T = any, R = any>(
    onCreate: (values: T) => Promise<R>,
    onEdit: (id: string | number, values: T) => Promise<R>,
) {
    const [opened, { open, close }] = useDisclosure(false);
    const [mode, setMode] = useState<FormMode>('create');
    const [editValues, setEditValues] = useState<Partial<T> | null>(null);

    function openCreate() {
        setEditValues(null);
        setMode('create');
        open();
    }

    function openEdit(values: Partial<T> & { id: string | number }) {
        setEditValues(values);
        setMode('edit');
        open();
    }

    // Submit já resolve create ou edit internamente
    async function handleSubmit(values: T) {
        if (mode === 'create') {
            await onCreate(values);
        } else {
            await onEdit(
                (editValues as Partial<T> & { id: string | number }).id,
                values,
            );
        }

        close();
    }

    return {
        formModalProps: {
            opened,
            mode,
            editValues,
            onClose: close,
            onSubmit: handleSubmit,
        } satisfies FormModalProps<T>,
        openCreate,
        openEdit,
    };
}
