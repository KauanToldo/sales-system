<script setup>
import { computed, onMounted, ref } from 'vue'
import { PhPencilSimpleLine, PhTrash } from '@phosphor-icons/vue'
import { toast } from 'vue3-toastify'
import http from '../api/http'
import SearchInput from '../components/common/SearchInput.vue'
import PrimaryActionButton from '../components/common/PrimaryActionButton.vue'
import PaginatedDataTable from '../components/common/PaginatedDataTable.vue'
import ConfirmDeleteModal from '../components/products/ConfirmDeleteModal.vue'
import PaymentMethodFormModal from '../components/payments/PaymentMethodFormModal.vue'

const paymentColumns = [
  {
    key: 'name',
    label: 'Method Name',
    cellClass: 'font-semibold',
  },
  {
    key: 'type',
    label: 'Type',
    cellClass: 'text-sales-ink capitalize',
  },
  {
    key: 'status',
    label: 'Status',
    cellClass: 'text-sales-ink',
  },
]

const methods = ref([])
const isLoading = ref(false)
const search = ref('')

const isFormModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isEditing = ref(false)
const isSubmittingForm = ref(false)
const isDeleting = ref(false)
const togglingId = ref(null)
const selectedMethod = ref(null)

const currentPage = ref(1)
const perPage = 10

const getApiErrorMessage = (error, fallback) => {
  return error?.response?.data?.error || fallback
}

const fetchMethods = async () => {
  isLoading.value = true

  try {
    const { data } = await http.get('/payment-methods')
    methods.value = Array.isArray(data) ? data : []

    const maxPage = Math.max(1, Math.ceil(methods.value.length / perPage))
    if (currentPage.value > maxPage) {
      currentPage.value = maxPage
    }
  } catch (error) {
    toast.error(getApiErrorMessage(error, 'Unable to load payment methods'))
  } finally {
    isLoading.value = false
  }
}

const filteredMethods = computed(() => {
  const term = search.value.trim().toLowerCase()

  if (!term) {
    return methods.value
  }

  return methods.value.filter((method) => {
    return [method.name, method.type, method.status ? 'active' : 'inactive']
      .map((value) => String(value || '').toLowerCase())
      .some((value) => value.includes(term))
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredMethods.value.length / perPage))
})

const paginatedMethods = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredMethods.value.slice(start, start + perPage)
})

const paginationLabel = computed(() => {
  const total = filteredMethods.value.length

  if (total === 0) {
    return 'Showing 0 to 0 of 0 entries'
  }

  const start = (currentPage.value - 1) * perPage + 1
  const end = Math.min(currentPage.value * perPage, total)

  return `Showing ${start} to ${end} of ${total} entries`
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 1)
  const end = Math.min(totalPages.value, start + 2)

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  return pages
})

const setPage = (page) => {
  if (page < 1 || page > totalPages.value) {
    return
  }

  currentPage.value = page
}

const openCreateModal = () => {
  isEditing.value = false
  selectedMethod.value = {
    name: '',
    type: 'ELECTRONIC',
    status: true,
  }
  isFormModalOpen.value = true
}

const openEditModal = (method) => {
  isEditing.value = true
  selectedMethod.value = {
    id: method.id,
    name: method.name,
    type: method.type,
    status: Boolean(method.status),
  }
  isFormModalOpen.value = true
}

const closeFormModal = () => {
  if (isSubmittingForm.value) {
    return
  }

  isFormModalOpen.value = false
}

const handleSubmitMethod = async (payload) => {
  isSubmittingForm.value = true

  try {
    if (isEditing.value && selectedMethod.value?.id) {
      await http.put(`/payment-methods/${selectedMethod.value.id}`, payload)
      toast.success('Payment method updated successfully')
    } else {
      await http.post('/payment-methods', payload)
      toast.success('Payment method created successfully')
    }

    isFormModalOpen.value = false
    await fetchMethods()
  } catch (error) {
    toast.error(getApiErrorMessage(error, 'Unable to save payment method'))
  } finally {
    isSubmittingForm.value = false
  }
}

const openDeleteModal = (method) => {
  selectedMethod.value = method
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  if (isDeleting.value) {
    return
  }

  isDeleteModalOpen.value = false
}

const handleDelete = async () => {
  if (!selectedMethod.value?.id) {
    return
  }

  isDeleting.value = true

  try {
    await http.delete(`/payment-methods/${selectedMethod.value.id}`)
    toast.success('Payment method deleted successfully')
    isDeleteModalOpen.value = false
    await fetchMethods()
  } catch (error) {
    toast.error(getApiErrorMessage(error, 'Unable to delete payment method'))
  } finally {
    isDeleting.value = false
  }
}

const handleToggleStatus = async (method) => {
  if (togglingId.value !== null) {
    return
  }

  togglingId.value = method.id

  try {
    await http.put(`/payment-methods/${method.id}`, {
      name: method.name,
      type: method.type,
      status: !Boolean(method.status),
    })

    toast.success(`Payment method ${!Boolean(method.status) ? 'activated' : 'deactivated'} successfully`)
    await fetchMethods()
  } catch (error) {
    toast.error(getApiErrorMessage(error, 'Unable to update payment method status'))
  } finally {
    togglingId.value = null
  }
}

onMounted(() => {
  fetchMethods()
})
</script>

<template>
  <section class="space-y-4">
    <header>
      <h1 class="font-display text-2xl font-semibold text-sales-tertiary">Payment Methods</h1>
      <p class="mt-1 text-sm text-sales-ink">Manage available payment methods and their active status.</p>
    </header>

    <div class="rounded-xl border border-sales-border bg-sales-surface shadow-sm">
      <div class="flex flex-col gap-3 border-b border-sales-border p-4 md:flex-row md:items-center md:justify-between">
        <div class="w-full md:max-w-sm">
          <SearchInput v-model="search" placeholder="Search payment methods..." />
        </div>

        <PrimaryActionButton label="Novo Método" @click="openCreateModal" />
      </div>

      <PaginatedDataTable
        :columns="paymentColumns"
        :rows="paginatedMethods"
        :loading="isLoading"
        loading-message="Loading payment methods..."
        empty-message="No payment methods found."
        :pagination-label="paginationLabel"
        :current-page="currentPage"
        :total-pages="totalPages"
        :visible-pages="visiblePages"
        show-actions
        actions-label="Actions"
        @prev="setPage(currentPage - 1)"
        @next="setPage(currentPage + 1)"
        @page="setPage"
      >
        <template #cell-status="{ row }">
          <button
            type="button"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition"
            :class="row.status ? 'bg-emerald-500' : 'bg-slate-300'"
            :disabled="togglingId === row.id"
            @click="handleToggleStatus(row)"
          >
            <span
              class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
              :class="row.status ? 'translate-x-5' : 'translate-x-1'"
            />
          </button>
          <span class="ml-2 text-xs font-semibold" :class="row.status ? 'text-emerald-700' : 'text-slate-500'">
            {{ row.status ? 'Active' : 'Inactive' }}
          </span>
        </template>

        <template #actions="{ row }">
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-md border border-sales-border px-2.5 py-1.5 text-xs font-semibold text-sales-tertiary transition hover:bg-sales-muted"
              @click="openEditModal(row)"
            >
              <PhPencilSimpleLine :size="14" />
              Edit
            </button>

            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
              @click="openDeleteModal(row)"
            >
              <PhTrash :size="14" />
              Delete
            </button>
          </div>
        </template>
      </PaginatedDataTable>
    </div>

    <PaymentMethodFormModal
      :open="isFormModalOpen"
      :is-editing="isEditing"
      :initial-data="selectedMethod || {}"
      :is-submitting="isSubmittingForm"
      @close="closeFormModal"
      @submit="handleSubmitMethod"
    />

    <ConfirmDeleteModal
      :open="isDeleteModalOpen"
      :is-submitting="isDeleting"
      title="Delete payment method"
      :message="`Are you sure you want to delete ${selectedMethod?.name || 'this payment method'}?`"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </section>
</template>
