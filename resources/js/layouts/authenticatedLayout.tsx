import type { ReactNode } from 'react';
import AppLayout from '@/layouts/AppLayout';

export function authenticatedLayout(page: ReactNode): ReactNode {
    return <AppLayout>{page}</AppLayout>;
}
