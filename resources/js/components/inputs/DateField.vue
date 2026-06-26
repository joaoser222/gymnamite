<template>
    <v-text-field
        v-model="inputValue"
        v-bind="dynamicProps"
        @blur="handleInput"
    >
        <template #append-inner>
            <v-menu
                location="end"
                :close-on-content-click="false"
                v-model="datePickerMenu"
            >
                <template v-slot:activator="{ props }">
                    <v-btn
                        icon="ti ti-calendar"
                        v-bind="props"
                        size="sm"
                        tabindex="-1"
                    ></v-btn>
                </template>
                <v-date-picker
                    elevation="24"
                    color="primary"
                    @update:modelValue="datePickerInput"
                ></v-date-picker>
            </v-menu>
        </template>
    </v-text-field>
</template>

<script setup lang="ts">
import { ref, watch, computed, useAttrs } from 'vue';
import moment from '@/plugins/moment';

/**
 * Campo de data com entrada manual e `v-date-picker`.
 *
 * O componente converte entre um formato amigável para exibição
 * (`formatDisplay`) e o formato esperado para persistência (`formatOutput`).
 */
const props = defineProps<{
    modelValue?: string;
    formatDisplay?: string;
    formatOutput?: string;
}>();

// Os formatos seguem defaults seguros, mas podem ser adaptados por tela.
const formatDisplay = props.formatDisplay ?? 'DD/MM/YYYY';
const formatOutput = props.formatOutput ?? 'YYYY-MM-DD';

// Emits
const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Estado
const inputValue = ref<string>('');
const datePickerMenu = ref<boolean>(false);

// Atributos
const attrs = useAttrs();

// Props dinâmicas
const dynamicProps = computed(() => ({
    ...attrs,
}));

// As helpers isolam conversão e limpeza quando a entrada não representa uma data válida.
function formatToDisplay(date: string | undefined): string {
    if (!date) return '';

    const momentObj = moment(date, formatOutput);
    if (momentObj.isValid()) {
        return momentObj.format(formatDisplay);
    }

    inputValue.value = '';
    return '';
}

function formatToOutput(date: string): string {
    const momentObj = moment(date, formatDisplay);
    if (momentObj.isValid()) {
        return momentObj.format(formatOutput);
    }

    inputValue.value = '';
    return '';
}

// O picker trabalha com um valor normalizado e emite no formato esperado pelo backend.
function datePickerInput(date: unknown): void {
    if (typeof date !== 'string') return;

    datePickerMenu.value = false;
    const formatted = moment(date).format(formatOutput);
    emit('update:modelValue', formatted);
}

// Mantém o texto sincronizado quando o valor externo muda por navegação ou preenchimento automático.
watch(
    () => props.modelValue,
    (newVal: string | undefined) => {
        if (newVal) {
            inputValue.value = formatToDisplay(newVal);
        }
    },
    { immediate: true },
);

// A conversão acontece no blur para evitar conflitos com a digitação do usuário.
function handleInput(): void {
    const formatted = formatToOutput(inputValue.value);
    emit('update:modelValue', formatted);
}
</script>
