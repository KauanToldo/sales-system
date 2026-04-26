<script setup>
import { computed, reactive, watch } from 'vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
  initialData: {
    type: Object,
    default: () => ({
      name: '',
      type: 'electronic',
      status: true,
    }),
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  name: '',
  type: 'electronic',
  status: true,
})

const fieldErrors = reactive({
  name: '',
  type: '',
})

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return
    }

    form.name = props.initialData.name || ''
    form.type = props.initialData.type || 'electronic'
    form.status = typeof props.initialData.status === 'boolean' ? props.initialData.status : true

    clearErrors()
  },
  { immediate: true }
)

const modalTitle = computed(() => (props.isEditing ? 'Edit Payment Method' : 'Add Payment Method'))
const submitLabel = computed(() => {
  if (props.isSubmitting) {
    return props.isEditing ? 'Saving...' : 'Creating...'
  }

  return props.isEditing ? 'Save changes' : 'Create payment method'
})

const clearErrors = () => {
  fieldErrors.name = ''
  fieldErrors.type = ''
}

const clearFieldError = (field) => {
  fieldErrors[field] = ''
}

const validateForm = () => {
  clearErrors()

  let valid = true

  if (!form.name.trim()) {
    fieldErrors.name = 'Payment method name is required'
    valid = false
  }

  if (!['electronic', 'physical'].includes(form.type)) {
    fieldErrors.type = 'Select a valid type'
    valid = false
  }

  return valid
}

const handleSubmit = () => {
  if (!validateForm()) {
    return
  }

  emit('submit', {
    name: form.name.trim(),
    type: form.type,
    status: form.status,
  })
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[60]">
      <div class="absolute inset-0 bg-slate-900/45" @click="emit('close')" />

      <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-xl rounded-xl border border-sales-border bg-sales-surface p-6 shadow-xl">
          <div class="mb-5">
            <h2 class="font-display text-2xl font-semibold text-sales-tertiary">{{ modalTitle }}</h2>
            <p class="mt-1 text-sm text-sales-ink">Fill all required fields to continue.</p>
          </div>

          <form class="space-y-4" @submit.prevent="handleSubmit">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Method name</label>
              <input
                v-model="form.name"
                type="text"
                class="h-10 w-full rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                :class="fieldErrors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : ''"
                @input="clearFieldError('name')"
              />
              <p v-if="fieldErrors.name" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.name }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Type</label>
                <select
                  v-model="form.type"
                  class="h-10 w-full rounded-md border border-sales-border bg-white px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                  :class="fieldErrors.type ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : ''"
                  @change="clearFieldError('type')"
                >
                  <option value="electronic">electronic</option>
                  <option value="physical">physical</option>
                </select>
                <p v-if="fieldErrors.type" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.type }}</p>
              </div>

              <div class="flex items-end pb-1">
                <label class="inline-flex items-center gap-2 text-sm text-sales-tertiary">
                  <input
                    v-model="form.status"
                    type="checkbox"
                    class="h-4 w-4 rounded border-sales-border text-sales-primary focus:ring-sales-primary/20"
                  />
                  Active status
                </label>
              </div>
            </div>

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
                type="submit"
                class="rounded-md bg-sales-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0067a9] disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="isSubmitting"
              >
                {{ submitLabel }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>
