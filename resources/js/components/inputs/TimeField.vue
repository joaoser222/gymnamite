<template>
  <v-text-field
    v-model="inputValue"
    v-bind="dynamicProps"
    @blur="handleInput"
  >
    <template #append-inner>
      <v-menu location="end" :close-on-content-click="false" v-model="timePickerMenu">
        <template v-slot:activator="{ props }">
          <v-btn icon="ti ti-clock" v-bind="props" size="sm" tabindex="-1"></v-btn>
        </template>
        <v-time-picker elevation="24" color="primary" @update:modelValue="timePickerInput"></v-time-picker>
      </v-menu>
    </template>
  </v-text-field>
</template>

<script setup lang="ts">
import { ref, watch, computed, useAttrs } from 'vue'
import moment from '@/plugins/moment'

interface Props {
  modelValue?: string
  formatDisplay?: string
  formatOutput?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  formatDisplay: 'HH:mm',
  formatOutput: 'HH:mm:ss'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const inputValue = ref<string>('')
const timePickerMenu = ref<boolean>(false)

const attrs = useAttrs()

const dynamicProps = computed<Record<string, unknown>>(() => {
  return {
    ...attrs
  }
})

function timePickerInput(time: string): void {
  timePickerMenu.value = false
  emit('update:modelValue', moment(time, 'HH:mm').format(props.formatOutput))
}

function formatToDisplay(time: string): string {
  const momentObj = moment(time, props.formatOutput)

  if (momentObj.isValid()) {
    return momentObj.format(props.formatDisplay)
  }

  inputValue.value = ''
  return ''
}

function formatToOutput(time: string): string {
  const momentObj = moment(time, props.formatDisplay)

  if (momentObj.isValid()) {
    return momentObj.format(props.formatOutput)
  }

  inputValue.value = ''
  return ''
}

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      inputValue.value = formatToDisplay(newVal)
    }
  },
  { immediate: true }
)

function handleInput(): void {
  emit('update:modelValue', formatToOutput(inputValue.value))
}
</script>