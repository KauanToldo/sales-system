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
      type: 'ELECTRONIC',
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
  type: 'ELECTRONIC',
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
    form.type = props.initialData.type || 'ELECTRONIC'
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

  if (!['ELECTRONIC', 'CASH'].includes(form.type)) {
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
                  <option value="ELECTRONIC">ELECTRONIC</option>
                  <option value="CASH">CASH</option>
                </select>
                <p v-if="fieldErrors.type" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.type }}</p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Status</label>
                <button
                  type="button"
                  class="inline-flex w-full items-center justify-between rounded-md border border-sales-border bg-white px-3 py-2 text-sm text-sales-tertiary transition hover:bg-sales-muted"
                  :class="form.status ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-300 bg-slate-50'"
                  @click="form.status = !form.status"
                >
                  <span class="font-medium">{{ form.status ? 'Active' : 'Inactive' }}</span>

                  <span
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                    :class="form.status ? 'bg-emerald-500' : 'bg-slate-300'"
                    aria-hidden="true"
                  >
                    <span
                      class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                      :class="form.status ? 'translate-x-5' : 'translate-x-1'"
                    />
                  </span>
                </button>
                <p class="mt-1 text-xs text-sales-ink">Inactive methods stay in history but cannot be used in new sales.</p>
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
