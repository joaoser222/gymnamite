import {
    AppShell,
    NavLink,
    Text,
    Avatar,
    Group,
    Stack,
    Button,
    ActionIcon,
    Drawer,
} from '@mantine/core';
import { Link, router, usePage } from '@inertiajs/react';
import { useDisclosure } from '@mantine/hooks';
import { useState } from 'react';
import { logout as logoutRoute } from '@/routes';
import {
    IconDashboard,
    IconUser,
    IconSettings,
    IconLogout,
    IconMenu2,
    IconChartBarPopular,
    IconClipboardData,
    IconReportSearch,
    IconPackage,
    IconTag,
    IconUserDollar,
    IconBuildingFactory2,
    IconStretching,
    IconShoppingCart,
    IconClipboardText,
    IconDiscount,
    IconRun,
    IconPackageExport,
    IconPackageImport,
    IconBuildingBank,
    IconFolderDollar,
    IconFilterDollar,
    IconCashMinus,
    IconCashPlus,
    IconCalendarDollar,
    IconReceiptDollar,
} from '@tabler/icons-react';

const navItems = [
    {
        name: 'dashboard',
        label: 'Dashboard',
        icon: IconDashboard,
        items: [
            { label: 'Painel', icon: IconChartBarPopular, route: '/dashboard' },
            { label: 'Relatórios', icon: IconClipboardData, route: '/reports' },
        ],
    },
    {
        name: 'catalog',
        label: 'Catálogo',
        icon: IconReportSearch,
        items: [
            { label: 'Produtos', icon: IconPackage, route: '/products' },
            { label: 'Modalidades', icon: IconTag, route: '/modalities' },
        ],
    },
    {
        name: 'people',
        label: 'Pessoas',
        icon: IconUser,
        items: [
            { label: 'Clientes', icon: IconUserDollar, route: '/clients' },
            { label: 'Fornecedores', icon: IconBuildingFactory2, route: '/suppliers' },
            { label: 'Instrutores', icon: IconStretching, route: '/trainers' },
        ],
    },
    {
        name: 'billing',
        label: 'Faturamento',
        icon: IconShoppingCart,
        items: [
            { label: 'Contratos', icon: IconClipboardText, route: '/contracts' },
            { label: 'Cupons', icon: IconDiscount, route: '/coupons' },
            { label: 'Aula Avulsa', icon: IconRun, route: '/direct-lessons' },
            { label: 'Compras', icon: IconPackageImport, route: '/purchases' },
            { label: 'Vendas', icon: IconPackageExport, route: '/sales' },
        ],
    },
    {
        name: 'financial',
        label: 'Financeiro',
        icon: IconBuildingBank,
        items: [
            { label: 'Contas', icon: IconBuildingBank, route: '/accounts' },
            { label: 'Centro de Custo', icon: IconFolderDollar, route: '/cost-centers' },
            { label: 'Categorias Financeiras', icon: IconFilterDollar, route: '/account-categories' },
            { label: 'Pagamentos', icon: IconCashMinus, route: '/payables' },
            { label: 'Recebimentos', icon: IconCashPlus, route: '/receivables' },
            { label: 'Movimentações', icon: IconCalendarDollar, route: '/settlements' },
            { label: 'Transferências', icon: IconReceiptDollar, route: '/transfers' },
        ],
    },
    {
        name: 'settings',
        label: 'Configurações',
        icon: IconSettings,
        items: [
            { label: 'Usuários', icon: IconUser, route: '/users' },
            { label: 'Meu Perfil', icon: IconUser, route: '/profile' },
            { label: 'Outras Configurações', icon: IconSettings, route: '/configs' },
        ],
    },
];

interface NavbarContentProps {
    opened: Record<string, boolean>;
    toggleGroup: (name: string) => void;
    currentPath: string;
    userName: string;
    onLogout: () => void;
}

function NavbarContent({ opened, toggleGroup, currentPath, userName, onLogout }: NavbarContentProps) {
    function getCurrentItem() {
        return navItems.flatMap((g) => g.items).find((i) => currentPath.startsWith(i.route));
    }

    return (
        <Stack justify="space-between" h="100%">
            <Stack gap={4}>
                {navItems.map(({ label, name, icon: Icon, items }) => (
                    <NavLink
                        key={name}
                        label={<Text fw={600} size="sm" style={{ flex: 1, textAlign: 'left' }}>{label}</Text>}
                        leftSection={<Icon size={20} />}
                        childrenOffset={24}
                        opened={!!opened[name]}
                        onClick={() => toggleGroup(name)}
                    >
                        {items.map((item) => (
                            <NavLink
                                key={item.route}
                                component={Link}
                                href={item.route}
                                label={<Text fw={600} size="sm" style={{ flex: 1, textAlign: 'left' }}>{item.label}</Text>}
                                leftSection={<item.icon size={20} />}
                                active={getCurrentItem()?.route === item.route}
                                my={6}
                            />
                        ))}
                    </NavLink>
                ))}
            </Stack>

            <Button
                variant="subtle"
                color="red"
                leftSection={<IconLogout size={16} />}
                onClick={onLogout}
                justify="start"
                fullWidth
            >
                Sair ({userName})
            </Button>
        </Stack>
    );
}

export default function AppLayout({ children }: { children: React.ReactNode }) {
    const { url, props } = usePage();
    const user = props.auth.user;
    const [mobileOpened, { toggle: toggleMobile }] = useDisclosure(false);
    const [desktopOpened, { toggle: toggleDesktop }] = useDisclosure(true);

    const [opened, setOpened] = useState<Record<string, boolean>>(() => {
        const activeGroup = navItems.find((g) => g.items.some((i) => url.startsWith(i.route)));
        return activeGroup ? { [activeGroup.name]: true } : {};
    });

    function toggleGroup(name: string) {
        setOpened((prev) => ({ ...prev, [name]: !prev[name] }));
    }

    function getCurrentItem() {
        return navItems.flatMap((g) => g.items).find((i) => url.startsWith(i.route));
    }

    function handleLogout() {
        router.post(logoutRoute.url());
    }

    const userInitial = user?.name?.charAt(0).toUpperCase() ?? 'U';

    return (
        <AppShell
            layout="alt"
            header={{ height: 60, offset: true }}
            navbar={{
                width: { sm: 250, lg: 280 },
                breakpoint: 'sm',
                collapsed: { mobile: !mobileOpened, desktop: !desktopOpened },
            }}
            padding="md"
        >
            <AppShell.Header px="md" bg="dark.8">
                <Group h="100%" justify="space-between">
                    <ActionIcon size={42} variant="transparent" onClick={toggleDesktop} visibleFrom="sm">
                        <IconMenu2 size={24} />
                    </ActionIcon>
                    <ActionIcon size={42} variant="transparent" onClick={toggleMobile} hiddenFrom="sm">
                        <IconMenu2 size={24} />
                    </ActionIcon>
                    <Text fw={600} size="lg" style={{ flex: 1, textAlign: 'left' }}>
                        {getCurrentItem()?.label ?? 'Gymnamite'}
                    </Text>
                    <Avatar radius="xl" size="sm" color="blue">{userInitial}</Avatar>
                </Group>
            </AppShell.Header>

            <Drawer
                opened={mobileOpened}
                onClose={toggleMobile}
                size={280}
                padding="sm"
                hiddenFrom="sm"
                withCloseButton={false}
                bg="dark.8"
            >
                <NavbarContent
                    opened={opened}
                    toggleGroup={toggleGroup}
                    currentPath={url}
                    userName={user?.name ?? ''}
                    onLogout={handleLogout}
                />
            </Drawer>

            <AppShell.Navbar p="sm" bg="dark.8" visibleFrom="sm">
                <NavbarContent
                    opened={opened}
                    toggleGroup={toggleGroup}
                    currentPath={url}
                    userName={user?.name ?? ''}
                    onLogout={handleLogout}
                />
            </AppShell.Navbar>

            <AppShell.Main bg="dark.9">{children}</AppShell.Main>
        </AppShell>
    );
}
