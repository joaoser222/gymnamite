<template>
    <v-text-field
        v-bind="$attrs"
        v-model="internalValue"
        :type="showPassword ? 'text' : 'password'"
        @input="onInput"
        @update:modelValue="onUpdateModelValue"
    >
        <template #append-inner>
            <v-btn
                :icon="showPassword ? 'eye-off' : 'eye'"
                tabindex="-1"
                @click="togglePasswordVisibility()"
                size="sm"
            >
            </v-btn>
        </template>
    </v-text-field>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

/**
 * Wrapper de `v-text-field` para campos de senha.
 *
 * Mantém API compatível com `v-model` e adiciona apenas o toggle de
 * visibilidade, evitando repetir essa lógica nas páginas.
 */
interface Props {
    modelValue?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'input', event: Event): void;
}>();

const internalValue = ref<string>(props.modelValue ?? '');
const showPassword = ref(false);

// Mantém o valor local sincronizado quando o formulário é atualizado externamente.
watch(
    () => props.modelValue,
    (newValue) => {
        internalValue.value = newValue ?? '';
    },
);

function togglePasswordVisibility(): void {
    showPassword.value = !showPassword.value;
}

function onInput(event: Event): void {
    emit('input', event);
}

function onUpdateModelValue(value: string): void {
    emit('update:modelValue', value);
}
</script>
