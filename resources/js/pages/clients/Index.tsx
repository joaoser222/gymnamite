import dayjs from 'dayjs';
import { router } from '@inertiajs/react';
import { TableColumn } from '@/components/ServerTable';
import ClientFormModal, { ClientFormPayload } from '@/features/client/form';
import { CrudPage } from '@/components/CrudPage';
import { useFormModal } from '@/hooks/useFormModal';
import { Client } from '@/features/client/types';
import { FetchParams, Paginator } from '@/types';
import { useAlert } from '@/hooks/useAlert';
import { index as clientsIndex, store as clientsStore, update as clientsUpdate } from '@/routes/clients';
import { authenticatedLayout } from '@/layouts/authenticatedLayout';

interface IndexProps {
    clients: Paginator<Client>;
    filters: FetchParams;
}

function Index({ clients, filters }: IndexProps) {
    const { showError, showSuccess } = useAlert();

    const { formModalProps, openCreate, openEdit } = useFormModal<ClientFormPayload>(
        (values) =>
            new Promise<void>((resolve, reject) => {
                router.post(clientsStore.url(), values, {
                    preserveScroll: true,
                    onSuccess: () => {
                        showSuccess('Cliente criado com sucesso.');
                        resolve();
                    },
                    onError: (errors) => {
                        showError(Object.values(errors)[0] as string);
                        reject(errors);
                    },
                });
            }),
        (id, values) =>
            new Promise<void>((resolve, reject) => {
                router.put(clientsUpdate.url(id), values, {
                    preserveScroll: true,
                    onSuccess: () => {
                        showSuccess('Cliente atualizado com sucesso.');
                        resolve();
                    },
                    onError: (errors) => {
                        showError(Object.values(errors)[0] as string);
                        reject(errors);
                    },
                });
            }),
    );

    const columns: TableColumn[] = [
        { key: 'name', title: 'Nome' },
        { key: 'document', title: 'CPF' },
        { key: 'phone', title: 'Telefone' },
        { key: 'status', title: 'Status', searchable: false },
        {
            key: 'created_at',
            title: 'Criado em',
            formatter: (item) => dayjs(item.created_at).format('DD/MM/YYYY'),
        },
        {
            key: 'updated_at',
            title: 'Atualizado em',
            formatter: (item) => dayjs(item.updated_at).format('DD/MM/YYYY'),
        },
    ];

    return (
        <CrudPage
            columns={columns}
            items={clients.data}
            pagination={{
                current_page: clients.current_page,
                last_page: clients.last_page,
                total: clients.total,
            }}
            filters={filters}
            listUrl={clientsIndex.url()}
            reloadOnly={['clients', 'filters']}
            onOpenCreate={openCreate}
            onOpenEdit={(row) => row.id && openEdit(row as Client & { id: number })}
            formModal={
                <ClientFormModal
                    {...formModalProps}
                    onSuccess={() => router.reload({ only: ['clients'] })}
                />
            }
        />
    );
}

Index.layout = authenticatedLayout;

export default Index;
