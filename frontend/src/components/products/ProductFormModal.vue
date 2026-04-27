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
      sku: '',
      name: '',
      category: '',
      price: '',
    }),
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  sku: '',
  name: '',
  category: '',
  price: '',
})

const fieldErrors = reactive({
  sku: '',
  name: '',
  category: '',
  price: '',
})

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return
    }

    form.sku = props.initialData.sku || ''
    form.name = props.initialData.name || ''
    form.category = props.initialData.category || ''
    form.price = props.initialData.price || ''

    clearErrors()
  },
  { immediate: true }
)

const modalTitle = computed(() => (props.isEditing ? 'Edit Product' : 'Add New Product'))
const submitLabel = computed(() => {
  if (props.isSubmitting) {
    return props.isEditing ? 'Saving...' : 'Creating...'
  }

  return props.isEditing ? 'Save changes' : 'Create product'
})

const clearErrors = () => {
  fieldErrors.sku = ''
  fieldErrors.name = ''
  fieldErrors.category = ''
  fieldErrors.price = ''
}

const clearFieldError = (field) => {
  fieldErrors[field] = ''
}

const isPriceValid = (value) => /^\d+(\.\d{1,2})?$/.test(value)

const validateForm = () => {
  clearErrors()

  let valid = true

  if (!form.sku.trim()) {
    fieldErrors.sku = 'SKU is required'
    valid = false
  }

  if (!form.name.trim()) {
    fieldErrors.name = 'Product name is required'
    valid = false
  }

  if (!form.category.trim()) {
    fieldErrors.category = 'Category is required'
    valid = false
  }

  if (!form.price.trim()) {
    fieldErrors.price = 'Price is required'
    valid = false
  } else if (!isPriceValid(form.price.trim())) {
    fieldErrors.price = 'Price must be a valid decimal value'
    valid = false
  } else if (Number(form.price.trim()) <= 0) {
    fieldErrors.price = 'Price must be greater than zero'
    valid = false
  }

  return valid
}

const handleSubmit = () => {
  if (!validateForm()) {
    return
  }

  emit('submit', {
    sku: form.sku.trim(),
    name: form.name.trim(),
    category: form.category.trim(),
    price: Number(form.price.trim()).toFixed(2),
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
              <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">SKU</label>
              <input
                v-model="form.sku"
                type="text"
                class="h-10 w-full rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                :class="fieldErrors.sku ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : ''"
                @input="clearFieldError('sku')"
              />
              <p v-if="fieldErrors.sku" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.sku }}</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Product name</label>
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
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Category</label>
                <input
                  v-model="form.category"
                  type="text"
                  class="h-10 w-full rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                  :class="fieldErrors.category ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : ''"
                  @input="clearFieldError('category')"
                />
                <p v-if="fieldErrors.category" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.category }}</p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-sales-ink">Price (BRL)</label>
                <input
                  v-model="form.price"
                  type="text"
                  inputmode="decimal"
                  placeholder="0.00"
                  class="h-10 w-full rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                  :class="fieldErrors.price ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : ''"
                  @input="clearFieldError('price')"
                />
                <p v-if="fieldErrors.price" class="mt-1 text-xs font-medium text-red-600">{{ fieldErrors.price }}</p>
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
