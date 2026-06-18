<template>
    <v-text-field
        v-bind="$attrs"
        :model-value="displayValue"
        @update:model-value="updateValue"
    >
        <template v-for="(_, name) in $slots" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps ?? {}" />
        </template>
    </v-text-field>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { applyMask, unmaskValue, type UnmaskMode } from '@/plugins/masks';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        mask?: string;
        unmask?: UnmaskMode;
    }>(),
    {
        modelValue: '',
        mask: '',
        unmask: 'mask',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const displayValue = computed(() =>
    applyMask(props.modelValue, props.mask, props.unmask),
);

function updateValue(value: string | null): void {
    emit('update:modelValue', unmaskValue(value, props.mask, props.unmask));
}
</script>
