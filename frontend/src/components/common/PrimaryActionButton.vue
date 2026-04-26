<script setup>
import { PhPlusCircle } from '@phosphor-icons/vue'

defineProps({
  label: {
    type: String,
    default: 'New item',
  },
  type: {
    type: String,
    default: 'button',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-sales-primary px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0067a9] disabled:cursor-not-allowed disabled:opacity-60"
  >
    <PhPlusCircle :size="16" weight="fill" />
    <span>{{ label }}</span>
  </button>
</template>
