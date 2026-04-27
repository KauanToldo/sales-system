<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  payments: {
    type: Array,
    default: () => [],
  },
  paymentMethods: {
    type: Array,
    default: () => [],
  },
  editableDraft: {
    type: Boolean,
    default: true,
  },
  canAddServerPayment: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  paymentErrors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['add-payment', 'update-payment', 'remove-payment'])

const methodId = ref('')
const amount = ref('')

const activeMethods = computed(() => props.paymentMethods.filter((method) => Boolean(method.status)))
const methodById = computed(() => {
  const map = new Map()

  for (const method of props.paymentMethods) {
    map.set(Number(method.id), method)
  }

  return map
})

const draftAmounts = reactive({})

watch(
  () => props.payments,
  (nextPayments) => {
    const nextKeys = new Set(nextPayments.map((payment, index) => String(payment.uiKey ?? payment.id ?? index)))

    nextPayments.forEach((payment, index) => {
      const key = String(payment.uiKey ?? payment.id ?? index)

      if (!Object.prototype.hasOwnProperty.call(draftAmounts, key)) {
        draftAmounts[key] = Number(payment.amount).toFixed(2)
      }
    })

    for (const key of Object.keys(draftAmounts)) {
      if (!nextKeys.has(key)) {
        delete draftAmounts[key]
      }
    }
  },
  { immediate: true, deep: true }
)

const getPaymentError = (index) => props.paymentErrors?.[String(index)] || ''

const handleAmountInput = (index, payment, event) => {
  const rawValue = event.target.value
  const key = String(payment.uiKey ?? payment.id ?? index)
  draftAmounts[key] = rawValue
  emit('update-payment', index, { payment_method_id: payment.payment_method_id, amount: rawValue })
}

const handleAdd = () => {
  emit('add-payment', {
    payment_method_id: Number(methodId.value),
    amount: amount.value,
  })

  methodId.value = ''
  amount.value = ''
}
</script>

<template>
  <div class="rounded-xl border border-sales-border bg-sales-surface p-4 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
      <h2 class="font-display text-lg font-semibold text-sales-tertiary">Payments</h2>
      <p class="text-xs text-sales-ink">Add one or more payments</p>
    </div>

    <div class="grid gap-3 md:grid-cols-[1fr_160px_auto]">
      <select
        v-model="methodId"
        class="h-10 rounded-md border border-sales-border bg-white px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
        :disabled="disabled || (!editableDraft && !canAddServerPayment)"
      >
        <option value="">Select method</option>
        <option v-for="method in activeMethods" :key="method.id" :value="method.id">
          {{ method.name }} ({{ method.type }})
        </option>
      </select>

      <input
        v-model="amount"
        type="text"
        placeholder="0.00"
        class="h-10 rounded-md border border-sales-border px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
        :disabled="disabled || (!editableDraft && !canAddServerPayment)"
      />

      <button
        type="button"
        class="h-10 rounded-md bg-sales-primary px-4 text-sm font-semibold text-white transition hover:bg-[#0067a9] disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="disabled || (!editableDraft && !canAddServerPayment)"
        @click="handleAdd"
      >
        Add payment
      </button>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full divide-y divide-sales-border text-sm">
        <thead class="bg-sales-muted/70">
          <tr>
            <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Method</th>
            <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Type</th>
            <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Amount</th>
            <th class="px-3 py-2 text-right font-semibold text-sales-tertiary">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-sales-border">
          <tr v-if="payments.length === 0">
            <td colspan="4" class="px-3 py-6 text-center text-sales-ink">No payments added yet</td>
          </tr>

              <tr v-for="(payment, index) in payments" :key="payment.uiKey ?? payment.id ?? `draft-${index}`">
            <td class="px-3 py-2 font-semibold text-sales-tertiary">
              {{ methodById.get(Number(payment.payment_method_id))?.name || `Method #${payment.payment_method_id}` }}
            </td>
            <td class="px-3 py-2 text-sales-ink">{{ methodById.get(Number(payment.payment_method_id))?.type || '-' }}</td>
            <td class="px-3 py-2">
              <input
                :value="draftAmounts[String(payment.uiKey ?? payment.id ?? index)] ?? Number(payment.amount).toFixed(2)"
                type="text"
                class="h-9 w-28 rounded-md border border-sales-border px-2 text-sm outline-none focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
                :disabled="!editableDraft || disabled"
                @input="handleAmountInput(index, payment, $event)"
              />
              <p v-if="getPaymentError(index)" class="mt-1 text-xs font-medium text-red-600">
                {{ getPaymentError(index) }}
              </p>
            </td>
            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="(!editableDraft && !canAddServerPayment) || disabled"
                @click="emit('remove-payment', index)"
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
