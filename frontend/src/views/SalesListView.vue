<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import { useSalesStore } from '../stores/sales'

const router = useRouter()
const salesStore = useSalesStore()

const activeStatus = ref('OPEN')

const customersById = computed(() => {
  const map = new Map()

  for (const customer of salesStore.customers) {
    map.set(Number(customer.id), customer)
  }

  return map
})

const filteredSales = computed(() => {
  return salesStore.salesList.filter((sale) => sale.status === activeStatus.value)
})

const formatDate = (value) => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date)
}

const openCheckout = (saleId) => {
  router.push({ name: 'point-of-sale', params: { id: saleId } })
}

const viewPdf = async (saleId) => {
  try {
    await salesStore.downloadSalePdf(saleId)
  } catch (error) {
    toast.error(error?.message || 'Unable to open PDF')
  }
}

const bootstrap = async () => {
  try {
    await Promise.all([salesStore.fetchSalesList(), salesStore.fetchLookups()])
  } catch (error) {
    toast.error(error?.message || 'Unable to load sales list')
  }
}

onMounted(() => {
  bootstrap()
})
</script>

<template>
  <section class="space-y-4">
    <header class="rounded-2xl border border-sales-border bg-sales-surface p-6 shadow-sm md:p-8">
      <h1 class="font-display text-2xl font-semibold text-sales-tertiary">Sales List</h1>
      <p class="mt-2 text-sm text-sales-ink">Track OPEN and FINALIZED sales, continue checkouts, and open PDF summaries.</p>
    </header>

    <div class="rounded-xl border border-sales-border bg-sales-surface p-4 shadow-sm">
      <div class="mb-4 inline-flex rounded-lg border border-sales-border bg-white p-1">
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-sm font-semibold"
          :class="activeStatus === 'OPEN' ? 'bg-sales-primary text-white' : 'text-sales-tertiary hover:bg-sales-muted'"
          @click="activeStatus = 'OPEN'"
        >
          Open
        </button>
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-sm font-semibold"
          :class="activeStatus === 'FINALIZED' ? 'bg-sales-primary text-white' : 'text-sales-tertiary hover:bg-sales-muted'"
          @click="activeStatus = 'FINALIZED'"
        >
          Finalized
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-sales-border text-sm">
          <thead class="bg-sales-muted/70">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">ID</th>
              <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Customer</th>
              <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Total</th>
              <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Paid</th>
              <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Change</th>
              <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Status</th>
              <th class="px-3 py-2 text-left font-semibold text-sales-tertiary">Created at</th>
              <th class="px-3 py-2 text-right font-semibold text-sales-tertiary">Action</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-sales-border">
            <tr v-if="salesStore.loadingList">
              <td colspan="8" class="px-3 py-6 text-center text-sales-ink">Loading sales...</td>
            </tr>

            <tr v-else-if="filteredSales.length === 0">
              <td colspan="8" class="px-3 py-6 text-center text-sales-ink">No sales found for this status</td>
            </tr>

            <tr v-for="sale in filteredSales" :key="sale.id">
              <td class="px-3 py-2 font-semibold text-sales-tertiary">#{{ sale.id }}</td>
              <td class="px-3 py-2 text-sales-ink">{{ customersById.get(Number(sale.customer_id))?.name || 'Walk-in' }}</td>
              <td class="px-3 py-2 text-sales-ink">{{ Number(sale.total).toFixed(2) }}</td>
              <td class="px-3 py-2 text-sales-ink">{{ Number(sale.total_paid).toFixed(2) }}</td>
              <td class="px-3 py-2 text-sales-ink">{{ Number(sale.change_amount).toFixed(2) }}</td>
              <td class="px-3 py-2">
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-semibold"
                  :class="sale.status === 'FINALIZED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ sale.status }}
                </span>
              </td>
              <td class="px-3 py-2 text-sales-ink">{{ formatDate(sale.created_at) }}</td>
              <td class="px-3 py-2 text-right">
                <div class="flex justify-end gap-2">
                  <button
                    v-if="sale.status === 'OPEN'"
                    type="button"
                    class="rounded-md border border-sales-border px-3 py-1.5 text-xs font-semibold text-sales-tertiary hover:bg-sales-muted"
                    @click="openCheckout(sale.id)"
                  >
                    Continue
                  </button>

                  <button
                    v-else
                    type="button"
                    class="rounded-md border border-sales-border px-3 py-1.5 text-xs font-semibold text-sales-tertiary hover:bg-sales-muted"
                    @click="openCheckout(sale.id)"
                  >
                    View
                  </button>

                  <button
                    v-if="sale.status === 'FINALIZED'"
                    type="button"
                    class="rounded-md bg-sales-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#0067a9]"
                    @click="viewPdf(sale.id)"
                  >
                    PDF
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
