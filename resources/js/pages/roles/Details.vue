<script setup lang="ts">
import { computed } from 'vue';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

type Role = {
    id: number;
    name: string;
    description: string;
};

const props = defineProps<{
    role: Role;
    routes: DetailsRoutes;
    selectedPermissionIds: number[];
    permissionGroups: Array<{
        id: string;
        title: string;
        permissions: Array<{
            id: number;
            label: string;
            description: string;
        }>;
    }>;
    isAdministrator: boolean;
}>();

const defaults = {
    permission_ids: props.selectedPermissionIds,
};

const title = computed(() => `Perfil: ${props.role.description}`);

const togglePermission = (
    permissionIds: number[],
    permissionId: number,
    enabled: boolean | null,
): number[] => {
    if (enabled) {
        return permissionIds.includes(permissionId)
            ? permissionIds
            : [...permissionIds, permissionId];
    }

    return permissionIds.filter((id) => id !== permissionId);
};
</script>

<template>
    <DetailsPage
        :title="title"
        :item="role"
        :defaults="defaults"
        :routes="routes"
        module="users"
        :can-save-override="!isAdministrator"
    >
        <template #default="{ form, errors }">
            <v-alert
                v-if="isAdministrator"
                type="info"
                variant="tonal"
                class="mb-4"
                text="O administrador possui todas as permissões e não pode ser alterado."
            />

            <v-alert
                v-if="errors.permission_ids"
                type="error"
                variant="tonal"
                class="mb-4"
                :text="errors.permission_ids"
            />

            <v-row class="ma-0">
                <v-col
                    v-for="group in permissionGroups"
                    :key="group.id"
                    cols="12"
                    md="6"
                >
                    <v-card variant="outlined" height="100%">
                        <v-card-title class="text-subtitle-1">
                            {{ group.title }}
                        </v-card-title>
                        <v-card-text>
                            <v-checkbox
                                v-for="permission in group.permissions"
                                :key="permission.id"
                                :label="permission.label"
                                :hint="permission.description"
                                persistent-hint
                                density="compact"
                                hide-details="auto"
                                :readonly="isAdministrator"
                                :model-value="form.permission_ids.includes(permission.id)"
                                @update:model-value="form.permission_ids = togglePermission(form.permission_ids, permission.id, $event)"
                            />
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
