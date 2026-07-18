<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type GatewaySetting = {
    key: string;
    label: string;
    type: string;
    required: boolean;
    default: unknown;
    options: { value: string; label: string }[] | null;
    placeholder: string | null;
    helpText: string | null;
};

type GatewayDefinition = {
    name: string;
    description: string;
    settings: GatewaySetting[];
};

type GatewayAccount = {
    id?: number;
    name?: string;
    description?: string;
    settings?: Record<string, unknown>;
};

const props = defineProps<{
    gatewayAccount?: GatewayAccount | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props as { options?: { definitions?: GatewayDefinition[]; providers?: { value: string; label: string; description: string }[] } };
const definitions = sharedProps.options?.definitions ?? [];
const providers = sharedProps.options?.providers ?? [];

const defaults = {
    name: '',
    description: '',
    settings: {} as Record<string, unknown>,
};

const isEditing = computed(() => props.gatewayAccount?.id != null);

const selectedProvider = ref(props.gatewayAccount?.name ?? '');

const currentDefinition = computed<GatewayDefinition | undefined>(() =>
    definitions.find((d) => d.name === selectedProvider.value),
);
</script>

<template>
    <DetailsPage
        title="Gateway"
        :item="gatewayAccount"
        :defaults="defaults"
        :routes="routes"
        module="gateway_accounts"
    >
        <template #default="{ form, errors, isCreating }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-select
                        v-if="isCreating"
                        v-model="selectedProvider"
                        label="Provedor"
                        :items="providers"
                        item-title="label"
                        item-value="value"
                        :rules="[required]"
                        :error-messages="errors.name"
                        @update:model-value="
                            (val) => {
                                form.name = val;
                                const def = definitions.find((d) => d.name === val);
                                if (def) {
                                    for (const setting of def.settings) {
                                        if (!(setting.key in form.settings)) {
                                            form.settings[setting.key] = setting.default ?? '';
                                        }
                                    }
                                }
                            }
                        "
                    />
                    <v-text-field
                        v-else
                        v-model="form.name"
                        label="Provedor"
                        readonly
                        :error-messages="errors.name"
                    />
                </v-col>

                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.description"
                        label="Descrição"
                        :error-messages="errors.description"
                    />
                </v-col>
            </v-row>

            <v-divider class="my-4" />

            <h3 class="text-subtitle-1 font-weight-medium mb-3">
                Configurações
            </h3>

            <v-row v-if="currentDefinition" class="ma-0">
                <v-col
                    v-for="setting in currentDefinition.settings"
                    :key="setting.key"
                    cols="12"
                    md="6"
                >
                    <v-select
                        v-if="setting.type === 'select'"
                        v-model="form.settings[setting.key]"
                        :label="setting.label"
                        :items="setting.options ?? []"
                        item-title="label"
                        item-value="value"
                        :rules="setting.required ? [required] : []"
                        :error-messages="errors[`settings.${setting.key}`]"
                        :persistent-hint="!!setting.helpText"
                        :hint="setting.helpText"
                    />
                    <v-text-field
                        v-else-if="setting.type === 'password'"
                        v-model="form.settings[setting.key]"
                        :label="setting.label"
                        type="password"
                        :rules="setting.required ? [required] : []"
                        :placeholder="setting.placeholder"
                        :error-messages="errors[`settings.${setting.key}`]"
                        :persistent-hint="!!setting.helpText"
                        :hint="setting.helpText"
                    />
                    <v-text-field
                        v-else
                        v-model="form.settings[setting.key]"
                        :label="setting.label"
                        :rules="setting.required ? [required] : []"
                        :placeholder="setting.placeholder"
                        :error-messages="errors[`settings.${setting.key}`]"
                        :persistent-hint="!!setting.helpText"
                        :hint="setting.helpText"
                    />
                </v-col>

                <v-col v-if="currentDefinition.settings.length === 0" cols="12">
                    <v-alert type="info" variant="tonal">
                        Nenhuma configuração adicional necessária para este provedor.
                    </v-alert>
                </v-col>
            </v-row>

            <v-row v-else class="ma-0">
                <v-col cols="12">
                    <v-alert type="info" variant="tonal">
                        Selecione um provedor para configurar.
                    </v-alert>
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
