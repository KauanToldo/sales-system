<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { PhPencilSimpleLine, PhTrash } from '@phosphor-icons/vue'
import { toast } from 'vue3-toastify'
import http from '../api/http'
import SearchInput from '../components/common/SearchInput.vue'
import PrimaryActionButton from '../components/common/PrimaryActionButton.vue'
import PaginatedDataTable from '../components/common/PaginatedDataTable.vue'
import ProductFormModal from '../components/products/ProductFormModal.vue'
import ConfirmDeleteModal from '../components/products/ConfirmDeleteModal.vue'

const productColumns = [
  {
    key: 'sku',
    label: 'SKU',
    cellClass: 'text-xs font-semibold uppercase text-slate-500',
  },
  {
    key: 'name',
    label: 'Product Name',
    cellClass: 'font-semibold',
  },
  {
    key: 'category',
    label: 'Category',
    cellClass: 'text-sales-ink',
  },
  {
    key: 'price',
    label: 'Price (BRL)',
    cellClass: 'font-semibold',
  },
]

const products = ref([])
const isLoading = ref(false)
const search = ref('')

const isFormModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isEditing = ref(false)
const isSubmittingForm = ref(false)
const isDeleting = ref(false)
const selectedProduct = ref(null)

const currentPage = ref(1)
const perPage = 10

const formatCurrency = (value) => {
  const amount = Number(value)

  if (Number.isNaN(amount)) {
    return 'R$ 0,00'
  }

  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 2,
  }).format(amount)
}

const getApiErrorMessage = (error, fallback) => {
  return error?.response?.data?.error || fallback
}

const fetchProducts = async () => {
  isLoading.value = true

  try {
    const { data } = await http.get('/products')
    products.value = Array.isArray(data) ? data : []

    const maxPage = Math.max(1, Math.ceil(products.value.length / perPage))
    if (currentPage.value > maxPage) {
      currentPage.value = maxPage
    }
  } catch (error) {
    toast.error(getApiErrorMessage(error, 'Unable to load products'))
  } finally {
    isLoading.value = false
  }
}

const filteredProducts = computed(() => {
  const term = search.value.trim().toLowerCase()

  if (!term) {
    return products.value
  }

  return products.value.filter((product) => {
    return [product.sku, product.name, product.category]
      .map((value) => String(value || '').toLowerCase())
      .some((value) => value.includes(term))
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredProducts.value.length / perPage))
})

const displayPage = computed(() => Math.min(currentPage.value, totalPages.value))

watch(search, () => {
  currentPage.value = 1
})

watch(totalPages, (pages) => {
  if (currentPage.value > pages) {
    currentPage.value = pages
  }
})

const paginatedProducts = computed(() => {
  const start = (displayPage.value - 1) * perPage
  return filteredProducts.value.slice(start, start + perPage)
})

const paginationLabel = computed(() => {
  const total = filteredProducts.value.length

  if (total === 0) {
    return 'Showing 0 to 0 of 0 entries'
  }

  const start = (displayPage.value - 1) * perPage + 1
  const end = Math.min(displayPage.value * perPage, total)

  return `Showing ${start} to ${end} of ${total} entries`
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, displayPage.value - 1)
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
  selectedProduct.value = {
    sku: '',
    name: '',
    category: '',
    price: '',
  }
  isFormModalOpen.value = true
}

const openEditModal = (product) => {
  isEditing.value = true
  selectedProduct.value = {
    id: product.id,
    sku: product.sku,
    name: product.name,
    category: product.category,
    price: String(product.price),
  }
  isFormModalOpen.value = true
}

const closeFormModal = () => {
  if (isSubmittingForm.value) {
    return
  }

  isFormModalOpen.value = false
}

const handleSubmitProduct = async (payload) => {
  isSubmittingForm.value = true

  try {
    if (isEditing.value && selectedProduct.value?.id) {
      await http.put(`/products/${selectedProduct.value.id}`, payload)
      toast.success('Product updated successfully')
    } else {
      await http.post('/products', payload)
      toast.success('Product created successfully')
    }

    isFormModalOpen.value = false
    await fetchProducts()
  } catch (error) {
    toast.error(getApiErrorMessage(error, 'Unable to save product'))
  } finally {
    isSubmittingForm.value = false
  }
}

const openDeleteModal = (product) => {
  selectedProduct.value = product
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  if (isDeleting.value) {
    return
  }

  isDeleteModalOpen.value = false
}

const handleDelete = async () => {
  if (!selectedProduct.value?.id) {
    return
  }

  isDeleting.value = true

  try {
    await http.delete(`/products/${selectedProduct.value.id}`)
    toast.success('Product deleted successfully')
    isDeleteModalOpen.value = false
    await fetchProducts()
  } catch (error) {
    toast.error(getApiErrorMessage(error, 'Unable to delete product'))
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <section class="space-y-4">
    <header>
      <h1 class="font-display text-2xl font-semibold text-sales-tertiary">Product Management</h1>
      <p class="mt-1 text-sm text-sales-ink">Manage your inventory, pricing, and categorizations.</p>
    </header>

    <div class="rounded-xl border border-sales-border bg-sales-surface shadow-sm">
      <div class="flex flex-col gap-3 border-b border-sales-border p-4 md:flex-row md:items-center md:justify-between">
        <div class="w-full md:max-w-sm">
          <SearchInput v-model="search" placeholder="Search products..." />
        </div>

        <PrimaryActionButton label="Novo Produto" @click="openCreateModal" />
      </div>

      <PaginatedDataTable
        :columns="productColumns"
        :rows="paginatedProducts"
        :loading="isLoading"
        loading-message="Loading products..."
        empty-message="No products found."
        :pagination-label="paginationLabel"
        :current-page="displayPage"
        :total-pages="totalPages"
        :visible-pages="visiblePages"
        show-actions
        actions-label="Actions"
        @prev="setPage(displayPage - 1)"
        @next="setPage(displayPage + 1)"
        @page="setPage"
      >
        <template #cell-price="{ row }">
          {{ formatCurrency(row.price) }}
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

    <ProductFormModal
      :open="isFormModalOpen"
      :is-editing="isEditing"
      :initial-data="selectedProduct || {}"
      :is-submitting="isSubmittingForm"
      @close="closeFormModal"
      @submit="handleSubmitProduct"
    />

    <ConfirmDeleteModal
      :open="isDeleteModalOpen"
      :is-submitting="isDeleting"
      title="Delete product"
      :message="`Are you sure you want to delete ${selectedProduct?.name || 'this product'}?`"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </section>
</template>
