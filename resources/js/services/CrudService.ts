import type { RequestPayload } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { FetchParams, Paginator, VisibilityOption } from '@/types';

export type { VisibilityOption, FetchParams, Paginator };

export interface PaginatedResponse<T> {
    results: T[];
    count: number;
    per_page: number;
    num_pages: number;
    page: number;
}

export interface ApiResponse<T> {
    data: T;
}

export class CrudService<T extends object> {
    protected resource: string = '';

    getAll = (_params: FetchParams): Promise<ApiResponse<PaginatedResponse<T>>> => {
        throw new Error('Use Inertia router.get instead of CrudService.getAll');
    };

    getById = (_id: string | number): Promise<ApiResponse<T>> => {
        throw new Error('Use Inertia router.get instead of CrudService.getById');
    };

    create = (data: Partial<T>): Promise<ApiResponse<T>> => {
        return new Promise((resolve, reject) => {
            router.post(`/${this.resource}`, data as RequestPayload, {
                onSuccess: () => resolve({ data: data as T }),
                onError: (errors) => reject(errors),
            });
        });
    };

    update = (id: string | number, data: Partial<T>): Promise<ApiResponse<T>> => {
        return new Promise((resolve, reject) => {
            router.put(`/${this.resource}/${id}`, data as RequestPayload, {
                onSuccess: () => resolve({ data: data as T }),
                onError: (errors) => reject(errors),
            });
        });
    };

    delete = (ids: (string | number)[]): Promise<ApiResponse<void>> => {
        return new Promise((resolve, reject) => {
            router.delete(`/${this.resource}/bulk-delete`, {
                data: { ids },
                onSuccess: () => resolve({ data: undefined }),
                onError: (errors) => reject(errors),
            });
        });
    };

    changeVisibility = (
        ids: (string | number)[],
        visibility: VisibilityOption,
    ): Promise<ApiResponse<void>> => {
        return new Promise((resolve, reject) => {
            router.put(`/${this.resource}/bulk-change-visibility`, { ids, visibility }, {
                onSuccess: () => resolve({ data: undefined }),
                onError: (errors) => reject(errors),
            });
        });
    };
}
