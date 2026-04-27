<script setup>
const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
  status: {
    type: String,
    default: 'OPEN',
  },
  showCashHint: {
    type: Boolean,
    default: true,
  },
})

const money = (value) => Number(value || 0).toFixed(2)
</script>

<template>
  <aside class="rounded-xl border border-sales-border bg-sales-surface p-4 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="font-display text-lg font-semibold text-sales-tertiary">Sale summary</h2>
      <span
        class="rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="status === 'FINALIZED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
      >
        {{ status }}
      </span>
    </div>

    <dl class="space-y-2 text-sm">
      <div class="flex items-center justify-between">
        <dt class="text-sales-ink">Total</dt>
        <dd class="font-semibold text-sales-tertiary">{{ money(summary.total) }}</dd>
      </div>
      <div class="flex items-center justify-between">
        <dt class="text-sales-ink">Total paid</dt>
        <dd class="font-semibold text-sales-tertiary">{{ money(summary.totalPaid) }}</dd>
      </div>
      <div class="flex items-center justify-between">
        <dt class="text-sales-ink">Change</dt>
        <dd class="font-semibold" :class="summary.change > 0 ? 'text-emerald-700' : 'text-sales-tertiary'">{{ money(summary.change) }}</dd>
      </div>
      <div class="flex items-center justify-between">
        <dt class="text-sales-ink">Missing</dt>
        <dd class="font-semibold" :class="summary.missing > 0 ? 'text-red-700' : 'text-sales-tertiary'">{{ money(summary.missing) }}</dd>
      </div>
    </dl>

    <p v-if="showCashHint" class="mt-4 rounded-md bg-slate-50 px-3 py-2 text-xs text-sales-ink">
      Change is only allowed when at least one CASH payment method is used.
    </p>
  </aside>
</template>
