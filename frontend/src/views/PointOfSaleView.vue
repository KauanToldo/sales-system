<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import { useSalesStore } from '../stores/sales'
import ProductPicker from '../components/sales/ProductPicker.vue'
import SaleItemsTable from '../components/sales/SaleItemsTable.vue'
import PaymentsEditor from '../components/sales/PaymentsEditor.vue'
import SaleSummaryCard from '../components/sales/SaleSummaryCard.vue'

const route = useRoute()
const router = useRouter()
const salesStore = useSalesStore()

const productsById = computed(() => {
  return salesStore.products.reduce((acc, product) => {
    acc[Number(product.id)] = product
    return acc
  }, {})
})

const status = computed(() => salesStore.currentSale?.status || 'OPEN')
const isDraftEditable = computed(() => !salesStore.isPersisted && !salesStore.isFinalized)
const canAddServerPayment = computed(() => salesStore.isPersisted && !salesStore.isFinalized)
const canFinalize = computed(() => {
  return salesStore.isPersisted && !salesStore.isFinalized && salesStore.totals.isPaidInFull && !salesStore.totals.overpayWithoutCash
})

const loadSaleFromRoute = async () => {
  const id = route.params.id ? Number(route.params.id) : null

  if (!id) {
    salesStore.initializeDraft()
    return
  }

  try {
    await salesStore.fetchSale(id)
  } catch (error) {
    toast.error(error?.message || 'Unable to load sale')
    router.replace({ name: 'point-of-sale' })
  }
}

const bootstrap = async () => {
  try {
    await salesStore.fetchLookups()
    await loadSaleFromRoute()
  } catch (error) {
    toast.error(error?.message || 'Unable to prepare checkout')
  }
}

const handleStartOrSave = async () => {
  try {
    const sale = await salesStore.createOpenSale()
    toast.success('Sale saved as OPEN')
    await router.replace({ name: 'point-of-sale', params: { id: sale.id } })
  } catch (error) {
    toast.error(error?.message || 'Unable to save sale')
  }
}

const handleFinalize = async () => {
  try {
    await salesStore.finalizeSale()
    toast.success('Sale finalized successfully')
  } catch (error) {
    toast.error(error?.message || 'Unable to finalize sale')
  }
}

const handleDownloadPdf = async () => {
  if (!salesStore.currentSale?.id) {
    return
  }

  try {
    await salesStore.downloadSalePdf(salesStore.currentSale.id)
  } catch (error) {
    toast.error(error?.message || 'Unable to generate PDF')
  }
}

const handleAddItem = ({ product, qty }) => {
  try {
    salesStore.addItem(product, qty)
  } catch (error) {
    toast.error(error?.message || 'Unable to add item')
  }
}

const handleUpdateItemQty = ({ productId, qty }) => {
  try {
    salesStore.updateItemQty(productId, qty)
  } catch (error) {
    toast.error(error?.message || 'Unable to update item')
  }
}

const handleRemoveItem = (productId) => {
  try {
    salesStore.removeItem(productId)
  } catch (error) {
    toast.error(error?.message || 'Unable to remove item')
  }
}

const handleAddPayment = async (payload) => {
  try {
    const savedOnServer = await salesStore.addPayment(payload)

    if (savedOnServer) {
      toast.success('Payment added')
    }
  } catch (error) {
    toast.error(error?.message || 'Unable to add payment')
  }
}

const handleUpdatePayment = (index, payload) => {
  try {
    salesStore.updatePayment(index, payload)
  } catch (error) {
    toast.error(error?.message || 'Unable to update payment')
  }
}

const handleRemovePayment = (index) => {
  try {
    salesStore.removePayment(index)
  } catch (error) {
    toast.error(error?.message || 'Unable to remove payment')
  }
}

onMounted(() => {
  bootstrap()
})

watch(
  () => route.params.id,
  () => {
    loadSaleFromRoute()
  }
)
</script>

<template>
  <section class="space-y-4">
    <header class="rounded-2xl border border-sales-border bg-sales-surface p-6 shadow-sm md:p-8">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="font-display text-2xl font-semibold text-sales-tertiary">Point of Sale</h1>
          <p class="mt-2 text-sm text-sales-ink">
            Draft your checkout as OPEN, finalize when paid in full, and generate PDF for FINALIZED sales.
          </p>
        </div>

        <span
          class="self-start rounded-full px-3 py-1 text-xs font-semibold"
          :class="status === 'FINALIZED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
        >
          {{ status }}
        </span>
      </div>
    </header>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
      <div class="space-y-4">
        <div class="rounded-xl border border-sales-border bg-sales-surface p-4 shadow-sm">
          <h2 class="font-display text-lg font-semibold text-sales-tertiary">Customer</h2>
          <p class="mb-3 mt-1 text-xs text-sales-ink">Choose a customer or keep as Walk-in.</p>

          <select
            v-model="salesStore.customerId"
            class="h-10 w-full rounded-md border border-sales-border bg-white px-3 text-sm outline-none transition focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15"
            :disabled="salesStore.isPersisted || salesStore.isFinalized"
          >
            <option :value="null">Walk-in</option>
            <option v-for="customer in salesStore.customers" :key="customer.id" :value="Number(customer.id)">
              {{ customer.name }}
            </option>
          </select>
        </div>

        <ProductPicker
          :products="salesStore.products"
          :disabled="!isDraftEditable"
          @add="handleAddItem"
        />

        <SaleItemsTable
          :items="salesStore.items"
          :products-by-id="productsById"
          :editable="isDraftEditable"
          @update-qty="handleUpdateItemQty"
          @remove="handleRemoveItem"
        />

        <PaymentsEditor
          :payments="salesStore.payments"
          :payment-methods="salesStore.paymentMethods"
          :editable-draft="isDraftEditable"
          :can-add-server-payment="canAddServerPayment"
          :disabled="salesStore.isFinalized"
          @add-payment="handleAddPayment"
          @update-payment="handleUpdatePayment"
          @remove-payment="handleRemovePayment"
        />

        <div
          v-if="salesStore.isPersisted && !salesStore.isFinalized"
          class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
          This OPEN sale is persisted. To keep backend consistency, item and existing-payment edits are locked and only new payments can be added.
        </div>

        <div
          v-if="salesStore.totals.overpayWithoutCash"
          class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          Overpayment without CASH is not allowed.
        </div>

        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-md bg-sales-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0067a9] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="salesStore.loading || salesStore.isPersisted || salesStore.isFinalized"
            @click="handleStartOrSave"
          >
            Start sale (OPEN)
          </button>

          <button
            type="button"
            class="rounded-md border border-sales-border px-4 py-2 text-sm font-semibold text-sales-tertiary transition hover:bg-sales-muted disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="!canFinalize || salesStore.loading"
            @click="handleFinalize"
          >
            Finalize sale
          </button>

          <button
            v-if="salesStore.isFinalized"
            type="button"
            class="rounded-md border border-sales-border px-4 py-2 text-sm font-semibold text-sales-tertiary transition hover:bg-sales-muted"
            @click="handleDownloadPdf"
          >
            Download PDF
          </button>
        </div>
      </div>

      <SaleSummaryCard
        :summary="salesStore.totals"
        :status="status"
        :show-cash-hint="salesStore.hasCashMethod"
      />
    </div>
  </section>
</template>
