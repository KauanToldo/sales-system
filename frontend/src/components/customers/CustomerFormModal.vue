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
      email: '',
      phone: '',
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
  email: '',
  phone: '',
})

const fieldErrors = reactive({
  name: '',
  email: '',
  phone: '',
})

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return
    }

    form.name = props.initialData.name || ''
    form.email = props.initialData.email || ''
    form.phone = props.initialData.phone || ''

    clearErrors()
  },
  { immediate: true }
)

const modalTitle = computed(() => (props.isEditing ? 'Edit Customer' : 'Add New Customer'))
const submitLabel = computed(() => {
  if (props.isSubmitting) {
    return props.isEditing ? 'Saving...' : 'Creating...'
  }

  return props.isEditing ? 'Save changes' : 'Create customer'
})

const clearErrors = () => {
  fieldErrors.name = ''
  fieldErrors.email = ''
  fieldErrors.phone = ''
}

const clearFieldError = (field) => {
  fieldErrors[field] = ''
}

const validateEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)

const validateForm = () => {
  clearErrors()

  let valid = true

  if (!form.name.trim()) {
    fieldErrors.name = 'Customer name is required'
    valid = false
  }

  if (!form.email.trim()) {
    fieldErrors.email = 'Email is required'
    valid = false
  } else if (!validateEmail(form.email.trim())) {
    fieldErrors.email = 'Enter a valid email address'
    valid = false
  }

  if (!form.phone.trim()) {
    fieldErrors.phone = 'Phone is required'
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
    email: form.email.trim(),
    phone: form.phone.trim(),
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
              <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Customer name</label>
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
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="h-10 w-full rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                  :class="fieldErrors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : ''"
                  @input="clearFieldError('email')"
                />
                <p v-if="fieldErrors.email" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.email }}</p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Phone</label>
                <input
                  v-model="form.phone"
                  type="text"
                  class="h-10 w-full rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                  :class="fieldErrors.phone ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : ''"
                  @input="clearFieldError('phone')"
                />
                <p v-if="fieldErrors.phone" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.phone }}</p>
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
