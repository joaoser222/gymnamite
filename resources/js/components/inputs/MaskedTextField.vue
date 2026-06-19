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

// Wrapper leve sobre `v-text-field` para centralizar exibição mascarada e valor emitido.
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

// `displayValue` controla apenas o que o usuário vê; o valor emitido pode ser mascarado ou limpo.
const displayValue = computed(() =>
    applyMask(props.modelValue, props.mask, props.unmask),
);

function updateValue(value: string | null): void {
    emit('update:modelValue', unmaskValue(value, props.mask, props.unmask));
}
</script>
