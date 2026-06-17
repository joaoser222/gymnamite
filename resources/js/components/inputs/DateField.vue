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

// Props
const props = defineProps<{
    modelValue?: string;
    formatDisplay?: string;
    formatOutput?: string;
}>();

// Valores padrão para props
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

// Métodos de formatação
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

// Handler do date picker
function datePickerInput(date: unknown): void {
    if (typeof date !== 'string') return;

    datePickerMenu.value = false;
    const formatted = moment(date).format(formatOutput);
    emit('update:modelValue', formatted);
}

// Watch para mudanças no modelValue
watch(
    () => props.modelValue,
    (newVal: string | undefined) => {
        if (newVal) {
            inputValue.value = formatToDisplay(newVal);
        }
    },
    { immediate: true },
);

// Handler do input manual
function handleInput(): void {
    const formatted = formatToOutput(inputValue.value);
    emit('update:modelValue', formatted);
}
</script>
