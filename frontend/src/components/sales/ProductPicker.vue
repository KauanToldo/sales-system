<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['add'])

const term = ref('')
const selectedId = ref('')
const qty = ref(1)

const filteredProducts = computed(() => {
  const normalizedTerm = term.value.trim().toLowerCase()

  if (!normalizedTerm) {
    return props.products.slice(0, 25)
  }

  return props.products
    .filter((product) => {
      return [product.name, product.sku, product.category]
        .map((value) => String(value || '').toLowerCase())
        .some((value) => value.includes(normalizedTerm))
    })
    .slice(0, 25)
})

const handleAdd = () => {
  const id = Number(selectedId.value)
  const quantity = Number(qty.value)

  if (!id || quantity <= 0) {
    return
  }

  const product = props.products.find((entry) => Number(entry.id) === id)
  if (!product) {
    return
  }

  emit('add', { product, qty: quantity })

  selectedId.value = ''
  qty.value = 1
}
</script>

<template>
  <div class="rounded-xl border border-sales-border bg-sales-surface p-4 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
      <h2 class="font-display text-lg font-semibold text-sales-tertiary">Items</h2>
      <p class="text-xs text-sales-ink">Search and add products to the sale</p>
    </div>

    <div class="grid gap-3 md:grid-cols-[1.4fr_1fr_120px_auto]">
      <input
        v-model="term"
        type="text"
        placeholder="Search by name, SKU, category"
        class="h-10 rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
        :disabled="disabled"
      />

      <select
        v-model="selectedId"
        class="h-10 rounded-md border border-sales-border bg-white px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
        :disabled="disabled"
      >
        <option value="">Select product</option>
        <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
          {{ product.sku }} - {{ product.name }} ({{ product.price }})
        </option>
      </select>

      <input
        v-model.number="qty"
        type="number"
        min="1"
        class="h-10 rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
        :disabled="disabled"
      />

      <button
        type="button"
        class="h-10 rounded-md bg-sales-primary px-4 text-sm font-semibold text-white transition hover:bg-[#0067a9] disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="disabled"
        @click="handleAdd"
      >
        Add item
      </button>
    </div>
  </div>
</template>
