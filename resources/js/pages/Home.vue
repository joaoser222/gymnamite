<script setup lang="ts">
import { computed, inject, ref, type ComputedRef } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { MenuGroup } from '@/navigation';

defineOptions({ layout: AuthenticatedLayout });

const search = ref('');
const menuGroups = inject<ComputedRef<MenuGroup[]>>('menuGroups');

const filteredMenuGroups = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();
    const authorizedGroups = menuGroups?.value ?? [];

    if (query === '') {
        return authorizedGroups;
    }

    return authorizedGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) =>
                item.title.toLocaleLowerCase().includes(query),
            ),
        }))
        .filter((group) => group.items.length > 0);
});
</script>

<template>
    <div class="mx-auto" style="max-width: 1200px">
        <div class="mb-8">
            <h1 class="text-h4">Aplicativos</h1>
            <p class="text-medium-emphasis mt-1">
                Acesse os módulos disponíveis para você.
            </p>
        </div>

        <v-text-field
            v-model="search"
            prepend-inner-icon="ti ti-search"
            label="Pesquisar aplicativo"
            variant="outlined"
            hide-details
            autofocus
        />

        <div v-if="filteredMenuGroups.length" class="mt-8">
            <section
                v-for="group in filteredMenuGroups"
                :key="group.name"
                class="mb-8"
            >
                <div class="d-flex align-center ga-2 mb-4">
                    <v-icon :icon="group.icon" size="20" />
                    <h2 class="text-h6">{{ group.title }}</h2>
                </div>
                <v-row>
                    <v-col
                        v-for="item in group.items"
                        :key="item.href"
                        cols="6"
                        sm="4"
                        md="3"
                        lg="2"
                    >
                        <v-card
                            class="h-100"
                            color="surface"
                            elevation="3"
                            hover
                            @click="router.visit(item.href)"
                        >
                            <v-card-text
                                class="d-flex flex-column align-center ga-3 pa-4"
                            >
                                <v-avatar
                                    class="clipped-object"
                                    color="primary"
                                    variant="tonal"
                                    size="58"
                                >
                                    <v-icon :icon="item.icon" size="28" />
                                </v-avatar>
                                <v-tooltip :text="item.title" location="bottom">
                                    <template
                                        #activator="{ props: tooltipProps }"
                                    >
                                        <span
                                            v-bind="tooltipProps"
                                            class="app-tile-title text-body-2 font-weight-medium text-center"
                                        >
                                            {{ item.title }}
                                        </span>
                                    </template>
                                </v-tooltip>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </section>
        </div>
        <v-empty-state
            v-else
            class="py-12"
            icon="ti ti-search-off"
            title="Nenhum aplicativo encontrado"
            text="Tente pesquisar por outro nome."
        />
    </div>
</template>

<style scoped>
.app-tile-title {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
