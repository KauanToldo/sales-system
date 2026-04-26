<script setup>
import { PhMagnifyingGlass } from '@phosphor-icons/vue'

defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Search...',
  },
})

const emit = defineEmits(['update:modelValue'])

const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
}
</script>

<template>
  <label class="relative block w-full">
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
      <PhMagnifyingGlass :size="16" />
    </span>
    <input
      :value="modelValue"
      :placeholder="placeholder"
      type="text"
      class="h-10 w-full rounded-md border border-sales-border bg-sales-surface pl-10 pr-3 text-sm text-sales-tertiary outline-none transition placeholder:text-slate-400 focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
      @input="handleInput"
    />
  </label>
</template>
