<script setup>
import { useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import {
  PhCube,
  PhUsers,
  PhCreditCard,
  PhStorefront,
  PhChartBar,
  PhPlus,
  PhSignOut,
  PhX,
  PhUserCircle,
} from '@phosphor-icons/vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'logout'])

const route = useRoute()
const authStore = useAuthStore()

const items = [
  { name: 'products', label: 'Products', icon: PhCube },
  { name: 'customers', label: 'Customers', icon: PhUsers },
  { name: 'payments', label: 'Payments', icon: PhCreditCard },
  { name: 'point-of-sale', label: 'Point of Sale', icon: PhStorefront },
  { name: 'reports', label: 'Reports', icon: PhChartBar },
]

const isActive = (name) => route.name === name
</script>

<template>
  <div
    v-if="props.isOpen"
    class="fixed inset-0 z-30 bg-slate-900/40 md:hidden"
    @click="emit('close')"
  />

  <aside
    class="fixed inset-y-0 left-0 z-40 flex w-[274px] flex-col border-r border-sales-border bg-[#F5F6F8] transition-transform duration-200 md:static md:translate-x-0"
    :class="props.isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    aria-label="Primary"
  >
    <div class="flex items-start justify-between border-b border-sales-border px-4 py-4">
      <div class="flex min-w-0 items-start gap-3">
        <div class="group relative mt-0.5">
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-full bg-sales-primary/10 text-sales-primary"
            aria-label="View user information"
          >
            <PhUserCircle :size="18" weight="fill" />
          </button>

          <div
            class="pointer-events-none absolute left-10 top-1/2 z-50 w-60 -translate-y-1/2 rounded-lg bg-slate-900 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition group-hover:opacity-100"
          >
            <p class="font-semibold">{{ authStore.user?.name || 'User' }}</p>
            <p class="mt-0.5 break-all text-slate-300">{{ authStore.user?.email || 'No email available' }}</p>
          </div>
        </div>

        <div class="min-w-0">
          <h1 class="font-display text-base font-semibold leading-tight text-sales-tertiary">Sales Zuchetti</h1>
          <p class="mt-0.5 text-[11px] leading-tight text-sales-ink">Sales Management</p>
        </div>
      </div>

      <button
        type="button"
        class="rounded-md p-1 text-sales-ink hover:bg-slate-200 md:hidden"
        @click="emit('close')"
        aria-label="Close menu"
      >
        <PhX :size="16" />
      </button>
    </div>

    <div class="px-4 pt-4">
      <RouterLink
        :to="{ name: 'point-of-sale' }"
        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-sales-primary px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#0067a9]"
        @click="emit('close')"
      >
        <PhPlus :size="14" />
        New Sale
      </RouterLink>
    </div>

    <nav class="mt-4 flex-1 px-2">
      <ul class="space-y-1">
        <li v-for="item in items" :key="item.name">
          <RouterLink
            :to="{ name: item.name }"
            class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition"
            :class="isActive(item.name)
              ? 'bg-sales-primary/10 text-sales-primary'
              : 'text-slate-600 hover:bg-slate-200 hover:text-sales-tertiary'"
            @click="emit('close')"
          >
            <component :is="item.icon" :size="16" weight="fill" />
            <span>{{ item.label }}</span>
          </RouterLink>
        </li>
      </ul>
    </nav>

    <div class="border-t border-sales-border p-3">
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-200 hover:text-sales-tertiary"
        @click="emit('logout')"
      >
        <PhSignOut :size="16" weight="fill" />
        Logout
      </button>
    </div>
  </aside>
</template>
