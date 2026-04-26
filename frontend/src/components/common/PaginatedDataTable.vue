<script setup>
defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  rowKey: {
    type: String,
    default: 'id',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  loadingMessage: {
    type: String,
    default: 'Loading data...',
  },
  emptyMessage: {
    type: String,
    default: 'No records found.',
  },
  paginationLabel: {
    type: String,
    default: '',
  },
  currentPage: {
    type: Number,
    default: 1,
  },
  totalPages: {
    type: Number,
    default: 1,
  },
  visiblePages: {
    type: Array,
    default: () => [1],
  },
  showActions: {
    type: Boolean,
    default: false,
  },
  actionsLabel: {
    type: String,
    default: 'Actions',
  },
})

const emit = defineEmits(['prev', 'next', 'page'])
</script>

<template>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-[#F7F9FB] text-[11px] uppercase tracking-[0.08em] text-slate-500">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            class="px-4 py-3 text-left font-semibold"
            :class="column.headerClass || ''"
          >
            {{ column.label }}
          </th>
          <th v-if="showActions" class="px-4 py-3 text-left font-semibold">{{ actionsLabel }}</th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="loading">
          <td :colspan="columns.length + (showActions ? 1 : 0)" class="px-4 py-8 text-center text-sales-ink">
            {{ loadingMessage }}
          </td>
        </tr>

        <tr v-else-if="rows.length === 0">
          <td :colspan="columns.length + (showActions ? 1 : 0)" class="px-4 py-8 text-center text-sales-ink">
            {{ emptyMessage }}
          </td>
        </tr>

        <tr
          v-for="row in rows"
          v-else
          :key="row[rowKey]"
          class="border-t border-sales-border text-sales-tertiary"
        >
          <td
            v-for="column in columns"
            :key="`${row[rowKey]}-${column.key}`"
            class="whitespace-nowrap px-4 py-3"
            :class="column.cellClass || ''"
          >
            <slot :name="`cell-${column.key}`" :row="row">
              {{ row[column.key] }}
            </slot>
          </td>

          <td v-if="showActions" class="whitespace-nowrap px-4 py-3">
            <slot name="actions" :row="row" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <footer class="flex flex-col gap-3 border-t border-sales-border px-4 py-3 text-xs text-sales-ink sm:flex-row sm:items-center sm:justify-between">
    <p>{{ paginationLabel }}</p>

    <div class="flex items-center gap-1">
      <button
        type="button"
        class="h-8 min-w-8 rounded-md border border-sales-border px-2 text-sm text-sales-tertiary transition hover:bg-sales-muted disabled:opacity-50"
        :disabled="currentPage === 1"
        @click="emit('prev')"
      >
        ‹
      </button>

      <button
        v-for="page in visiblePages"
        :key="page"
        type="button"
        class="h-8 min-w-8 rounded-md border px-2 text-sm transition"
        :class="page === currentPage
          ? 'border-sales-primary bg-sales-primary text-white'
          : 'border-sales-border text-sales-tertiary hover:bg-sales-muted'"
        @click="emit('page', page)"
      >
        {{ page }}
      </button>

      <button
        type="button"
        class="h-8 min-w-8 rounded-md border border-sales-border px-2 text-sm text-sales-tertiary transition hover:bg-sales-muted disabled:opacity-50"
        :disabled="currentPage === totalPages"
        @click="emit('next')"
      >
        ›
      </button>
    </div>
  </footer>
</template>
