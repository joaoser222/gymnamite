<template>
    <BaseLayout :menu="visibleMenuGroups">
        <slot />
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import BaseLayout from '@/layouts/BaseLayout.vue';

type MenuItem = {
    title: string;
    icon: string;
    href: string;
    permission?: string;
};

type MenuGroup = {
    name: string;
    title: string;
    icon: string;
    items: MenuItem[];
};

const { can, loadPermissions } = usePermissions();

const menuGroups: MenuGroup[] = [
    {
        name: 'start',
        title: 'Início',
        icon: 'ti ti-home',
        items: [
            {
                title: 'Dashboard',
                icon: 'ti ti-home',
                href: '/dashboard',
            },
        ],
    },
    {
        name: 'catalog',
        title: 'Catálogo',
        icon: 'ti ti-book',
        items: [
            {
                title: 'Planos',
                icon: 'ti ti-briefcase',
                href: '/plans',
                permission: 'plans.view',
            },
            {
                title: 'Categorias de Plano',
                icon: 'filter',
                href: '/plan-categories',
                permission: 'plan_categories.view',
            },
            {
                title: 'Modalidades',
                icon: 'ti ti-gymnastics',
                href: '/modalities',
                permission: 'modalities.view',
            },
            {
                title: 'Produtos',
                icon: 'ti ti-packages',
                href: '/products',
                permission: 'products.view',
            },
        ],
    },
    {
        name: 'peoples',
        title: 'Pessoas',
        icon: 'ti ti-users',
        items: [
            {
                title: 'Clientes',
                icon: 'ti ti-user',
                href: '/clients',
                permission: 'clients.view',
            },
            {
                title: 'Treinadores',
                icon: 'ti ti-stretching',
                href: '/trainers',
                permission: 'trainers.view',
            },
            {
                title: 'Fornecedores',
                icon: 'ti ti-buildings',
                href: '/suppliers',
                permission: 'suppliers.view',
            },
        ],
    },
    {
        name: 'billing',
        title: 'Faturamento',
        icon: 'ti ti-report-money',
        items: [
            {
                title: 'Contratos',
                icon: 'ti ti-contract',
                href: '/contracts',
                permission: 'contracts.view',
            },
            {
                title: 'Cupons',
                icon: 'ti ti-circle-dashed-percentage',
                href: '/coupons',
                permission: 'coupons.view',
            },
            {
                title: 'Compras',
                icon: 'ti ti-shopping-cart',
                href: '/purchases',
                permission: 'purchases.view',
            },
            {
                title: 'Vendas',
                icon: 'ti ti-basket',
                href: '/sales',
                permission: 'sales.view',
            },
            {
                title: 'Aula Avulsa',
                icon: 'ti ti-barbell',
                href: '/direct-lessons',
                permission: 'direct_lessons.view',
            },
        ],
    },
    {
        name: 'financial',
        title: 'Financeiro',
        icon: 'ti ti-cash-banknote',
        items: [
            {
                title: 'Contas',
                icon: 'ti ti-building-bank',
                href: '/financial-accounts',
                permission: 'financial_accounts.view',
            },
            {
                title: 'Centros de Custo',
                icon: 'ti ti-filter-dollar',
                href: '/cost-centers',
                permission: 'cost_centers.view',
            },
            {
                title: 'Categorias Financeiras',
                icon: 'ti ti-filter-2-dollar',
                href: '/financial-categories',
                permission: 'financial_categories.view',
            },
            {
                title: 'Pagamentos',
                icon: 'ti ti-cash-banknote-minus',
                href: '/payables',
                permission: 'payables.view',
            },
            {
                title: 'Recebimentos',
                icon: 'ti ti-cash-banknote-plus',
                href: '/receivables',
                permission: 'receivables.view',
            },
            {
                title: 'Caixa',
                icon: 'ti ti-cash-register',
                href: '/movements',
                permission: 'movements.view',
            },
            {
                title: 'Transferências',
                icon: 'ti ti-cash-banknote-move',
                href: '/transfers',
                permission: 'transfers.view',
            },
        ],
    },
    {
        name: 'advanced',
        title: 'Avançado',
        icon: 'ti ti-settings',
        items: [
            {
                title: 'Usuários',
                icon: 'ti ti-user-shield',
                href: '/users',
                permission: 'users.view',
            },
            {
                title: 'Configurações',
                icon: 'ti ti-adjustments',
                href: '/settings',
                permission: 'settings.view',
            },
        ],
    },
];

const visibleMenuGroups = computed(() => {
    return menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                return item.permission === undefined || can(item.permission);
            }),
        }))
        .filter((group) => group.items.length > 0);
});

onMounted(() => {
    void loadPermissions();
});
</script>
