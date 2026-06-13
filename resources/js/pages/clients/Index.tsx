import { router } from '@inertiajs/react';
import dayjs from 'dayjs';
import { CrudPage } from '@/components/CrudPage';
import type { TableColumn } from '@/components/ServerTable';
import { useAlert } from '@/hooks/useAlert';
import { useFormModal } from '@/hooks/useFormModal';
import { authenticatedLayout } from '@/layouts/authenticatedLayout';
import {
    index as clientsIndex,
    store as clientsStore,
    update as clientsUpdate,
} from '@/routes/clients';
import type { FetchParams, Paginator } from '@/types';
import type { ClientFormPayload } from './Form';
import ClientFormModal from './Form';
import type { Client } from './types';

interface IndexProps {
    clients: Paginator<Client>;
    filters: FetchParams;
}

function Index({ clients, filters }: IndexProps) {
    const { showError, showWarning } = useAlert();

    function firstErrorMessage(errors: Record<string, unknown>): string {
        const firstError = Object.values(errors)[0];

        if (Array.isArray(firstError)) {
            return String(firstError[0] ?? 'Não foi possível salvar.');
        }

        return String(firstError ?? 'Não foi possível salvar.');
    }

    function httpExceptionMessage(status: number): string {
        if (status === 403) {
            return 'Você não tem permissão para executar esta ação.';
        }

        if (status === 419) {
            return 'Sua sessão expirou. Recarregue a página e tente novamente.';
        }

        return 'Não foi possível concluir a operação.';
    }

    const { formModalProps, openCreate, openEdit } =
        useFormModal<ClientFormPayload>(
            (values) =>
                new Promise<void>((resolve, reject) => {
                    router.post(clientsStore.url(), values, {
                        preserveScroll: true,
                        onSuccess: () => {
                            resolve();
                        },
                        onError: (errors) => {
                            showError(firstErrorMessage(errors));
                            reject(errors);
                        },
                        onHttpException: (response) => {
                            showWarning(httpExceptionMessage(response.status));
                            reject(response);
                        },
                    });
                }),
            (id, values) =>
                new Promise<void>((resolve, reject) => {
                    router.put(clientsUpdate.url(Number(id)), values, {
                        preserveScroll: true,
                        onSuccess: () => {
                            resolve();
                        },
                        onError: (errors) => {
                            showError(firstErrorMessage(errors));
                            reject(errors);
                        },
                        onHttpException: (response) => {
                            showWarning(httpExceptionMessage(response.status));
                            reject(response);
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
            onOpenEdit={(row) =>
                row.id && openEdit(row as Client & { id: number })
            }
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
