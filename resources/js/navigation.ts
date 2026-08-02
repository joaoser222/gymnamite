export type MenuItem = {
    title: string;
    icon: string;
    href: string;
    permission?: string;
};

export type MenuGroup = {
    name: string;
    title: string;
    icon: string;
    items: MenuItem[];
};

export function visibleMenuGroups(
    menuGroups: MenuGroup[],
    can: (permission: string) => boolean,
): MenuGroup[] {
    return menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                return item.permission === undefined || can(item.permission);
            }),
        }))
        .filter((group) => group.items.length > 0);
}
