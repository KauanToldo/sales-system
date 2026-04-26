<script setup>
defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Delete item',
  },
  message: {
    type: String,
    default: 'Are you sure you want to delete this item?',
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[60]">
      <div class="absolute inset-0 bg-slate-900/45" @click="emit('close')" />

      <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-sales-border bg-sales-surface p-6 shadow-xl">
          <h2 class="font-display text-xl font-semibold text-sales-tertiary">{{ title }}</h2>
          <p class="mt-2 text-sm text-sales-ink">{{ message }}</p>

          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="rounded-md border border-sales-border px-4 py-2 text-sm font-semibold text-sales-tertiary transition hover:bg-sales-muted"
              :disabled="isSubmitting"
              @click="emit('close')"
            >
              Cancel
            </button>

            <button
              type="button"
              class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isSubmitting"
              @click="emit('confirm')"
            >
              {{ isSubmitting ? 'Deleting...' : 'Confirm' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
