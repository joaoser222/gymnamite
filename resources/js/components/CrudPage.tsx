import { Paper } from '@mantine/core';
import { router } from '@inertiajs/react';
import { ServerTable, TableColumn } from '@/components/ServerTable';
import { ActionToolbar } from './ActionToolbar';
import { FormModalProps } from '@/hooks/useFormModal';
import { FetchParams, Paginator, VisibilityOption } from '@/types';

export interface CrudPageProps<T extends object> {
    columns: TableColumn[];
    items: T[];
    pagination: Pick<Paginator<T>, 'current_page' | 'last_page' | 'total'>;
    filters: FetchParams;
    listUrl: string;
    reloadOnly?: string[];
    formModal: React.ReactElement<FormModalProps>;
    onOpenCreate: () => void;
    onOpenEdit?: (row: T) => void;
    loading?: boolean;
}

export function CrudPage<T extends object>({
    columns,
    items,
    pagination,
    filters,
    listUrl,
    reloadOnly = [],
    formModal,
    onOpenCreate,
    onOpenEdit,
    loading = false,
}: CrudPageProps<T>) {
    function visit(params: Partial<FetchParams>) {
        router.get(
            listUrl,
            { ...filters, ...params },
            {
                preserveState: true,
                replace: true,
                only: reloadOnly.length > 0 ? reloadOnly : undefined,
            },
        );
    }

    return (
        <>
            {formModal}

            <ActionToolbar
                columns={columns}
                onVisibilityChange={(visibility: VisibilityOption) =>
                    visit({ visibility, page: 1 })
                }
                onSearchChange={(field, search) =>
                    visit({ searchField: field, search, page: 1 })
                }
                onCreate={onOpenCreate}
            />

            {loading ? (
                <Paper p="xl" ta="center">Carregando...</Paper>
            ) : (
                <ServerTable
                    columns={columns}
                    items={items}
                    totalItems={pagination.total}
                    totalPages={pagination.last_page}
                    page={pagination.current_page}
                    onPageChange={(page) => visit({ page })}
                    onEditRow={onOpenEdit}
                />
            )}
        </>
    );
}
