<script setup lang="ts">
type StatusOption = {
    value: string;
    label?: string;
    color?: string;
};

const props = defineProps<{
    options: Record<string, StatusOption> | StatusOption[];
    value: string | null | undefined;
}>();

function optionFor(): StatusOption | undefined {
    if (Array.isArray(props.options)) {
        return props.options.find((option) => option.value === props.value);
    }

    return props.value ? props.options[props.value] : undefined;
}
</script>

<template>
    <v-chip
        :color="optionFor()?.color ?? 'secondary'"
        variant="tonal"
        rounded="sm"
        class="clipped-object-sm"
    >
        {{ optionFor()?.label ?? value ?? '-' }}
    </v-chip>
</template>
