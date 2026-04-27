<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  productsById: {
    type: Object,
    default: () => ({}),
  },
  editable: {
    type: Boolean,
    default: true,
  },
  itemErrors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update-qty', 'remove'])

const draftQuantities = reactive({})

watch(
  () => props.items,
  (items) => {
    const nextKeys = new Set(items.map((item) => String(item.product_id)))

    for (const item of items) {
      const key = String(item.product_id)

      if (!Object.prototype.hasOwnProperty.call(draftQuantities, key)) {
        draftQuantities[key] = String(item.quantity ?? '')
      }
    }

    for (const key of Object.keys(draftQuantities)) {
      if (!nextKeys.has(key)) {
        delete draftQuantities[key]
      }
    }
  },
  { immediate: true, deep: true }
)

const lineTotal = (item) => Number(item.quantity || 0) * Number(item.unit_price || 0)

const getItemError = (productId) => props.itemErrors?.[String(productId)] || ''

const handleQtyInput = (item, event) => {
  const rawValue = event.target.value
  draftQuantities[String(item.product_id)] = rawValue
  emit('update-qty', { productId: item.product_id, qty: rawValue })
}
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-sales-border bg-sales-surface shadow-sm">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-sales-border text-sm">
        <thead class="bg-sales-muted/70">
          <tr>
            <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Product</th>
            <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Unit price</th>
            <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Qty</th>
            <th class="px-3 py-2 text-right font-semibold text-sales-tertiary">Line total</th>
            <th class="px-3 py-2 text-right font-semibold text-sales-tertiary">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-sales-border">
          <tr v-if="items.length === 0">
            <td colspan="5" class="px-3 py-6 text-center text-sales-ink">No items added yet</td>
          </tr>

          <tr v-for="item in items" :key="`${item.product_id}-${item.id ?? 'draft'}`">
            <td class="px-3 py-2">
              <p class="font-semibold text-sales-tertiary">{{ productsById[item.product_id]?.name || `Product #${item.product_id}` }}</p>
              <p class="text-xs text-sales-ink">SKU {{ productsById[item.product_id]?.sku || '-' }}</p>
            </td>
            <td class="px-3 py-2 text-sales-ink">{{ Number(item.unit_price).toFixed(2) }}</td>
            <td class="px-3 py-2">
              <input
                :value="draftQuantities[String(item.product_id)] ?? String(item.quantity ?? '')"
                type="text"
                inputmode="numeric"
                min="1"
                class="h-9 w-20 rounded-md border border-sales-border px-2 text-sm outline-none focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                :disabled="!editable"
                @input="handleQtyInput(item, $event)"
              />
              <p v-if="getItemError(item.product_id)" class="mt-1 text-xs font-medium text-red-600">
                {{ getItemError(item.product_id) }}
              </p>
            </td>
            <td class="px-3 py-2 text-right font-semibold text-sales-tertiary">{{ lineTotal(item).toFixed(2) }}</td>
            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!editable"
                @click="emit('remove', item.product_id)"
              >
                Remove
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
