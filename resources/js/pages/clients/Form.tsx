import { TextInput, Grid } from '@mantine/core';
import { DateInput } from '@mantine/dates';
import { useForm } from '@mantine/form';
import { useEffect } from 'react';
import { FormModal } from '@/components/FormModal';
import { CpfInput, PhoneInput } from '@/components/inputs';
import type { FormModalProps } from '@/hooks/useFormModal';
import { validators } from '@/utils/validators';
import type { Client } from './types';

export type ClientFormPayload = Omit<Client, 'id'>;

const initialValues: ClientFormPayload = {
    name: '',
    email: '',
    phone: '',
    document: '',
    gender: '',
    birth_date: '',
    legal_representative: false,
    legal_representative_name: null,
    legal_representative_document: null,
    legal_representative_birth_date: null,
    address_postal_code: '',
    address: '',
    address_number: '',
    address_complement: '',
    address_district: '',
    address_state: '',
    address_city: '',
};

interface ClientFormModalProps extends FormModalProps<ClientFormPayload> {
    onSuccess?: () => void;
}

export default function ClientFormModal({
    opened,
    mode,
    editValues,
    onClose,
    onSubmit,
    onSuccess,
}: ClientFormModalProps) {
    const form = useForm<ClientFormPayload>({
        initialValues,
        validate: {
            name: validators.compose('Nome', validators.required()),
            email: validators.compose(
                'E-mail',
                validators.required(),
                validators.email(),
            ),
            phone: validators.compose(
                'Telefone',
                validators.required(),
                validators.minLength(11),
            ),
            document: validators.compose(
                'CPF',
                validators.required(),
                validators.minLength(11),
            ),
        },
    });
    useEffect(() => {
        if (!opened) {
            return;
        }

        if (editValues) {
            form.setValues({ ...initialValues, ...editValues });
        } else {
            form.reset();
        }
    }, [opened, editValues, form]);

    async function handleSubmit(values: ClientFormPayload) {
        await onSubmit(values);
        onSuccess?.();
    }

    return (
        <FormModal
            opened={opened}
            onClose={onClose}
            title={mode === 'create' ? 'Novo Cliente' : 'Editar Cliente'}
            onSubmit={form.onSubmit(handleSubmit as any)}
        >
            <Grid>
                <Grid.Col span={{ base: 12, sm: 6 }}>
                    <TextInput label="Nome" {...form.getInputProps('name')} />
                </Grid.Col>
                <Grid.Col span={{ base: 12, sm: 6 }}>
                    <TextInput
                        label="E-mail"
                        {...form.getInputProps('email')}
                    />
                </Grid.Col>
                <Grid.Col span={{ base: 12, sm: 6 }}>
                    <CpfInput label="CPF" {...form.getInputProps('document')} />
                </Grid.Col>
                <Grid.Col span={{ base: 12, sm: 6 }}>
                    <PhoneInput
                        label="Telefone"
                        {...form.getInputProps('phone')}
                    />
                </Grid.Col>
                <Grid.Col span={{ base: 12, sm: 6 }}>
                    <PhoneInput
                        label="Telefone"
                        {...form.getInputProps('phone')}
                    />
                </Grid.Col>
                <Grid.Col span={{ base: 12, sm: 6 }}>
                    <DateInput
                        label="Data de Nasc."
                        placeholder="DD/MM/YYYY"
                        valueFormat="DD/MM/YYYY"
                        {...form.getInputProps('birth_date')}
                    />
                </Grid.Col>
            </Grid>
        </FormModal>
    );
}
