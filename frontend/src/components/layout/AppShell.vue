<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { PhList } from '@phosphor-icons/vue'
import { useAuthStore } from '../../stores/auth'
import AppSidebar from './AppSidebar.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)

const pageTitle = computed(() => (route.meta.title ? String(route.meta.title) : 'Dashboard'))

const closeSidebar = () => {
  isSidebarOpen.value = false
}

const handleLogout = () => {
  authStore.logout()
  router.push({ name: 'login' })
}

onMounted(async () => {
  if (!authStore.isAuthenticated()) {
    await authStore.checkAuth()

    if (!authStore.isAuthenticated()) {
      router.push({ name: 'login' })
    }
  }
})
</script>

<template>
  <div class="min-h-screen bg-sales-neutral md:flex">
    <AppSidebar :is-open="isSidebarOpen" @close="closeSidebar" @logout="handleLogout" />

    <div class="flex min-h-screen flex-1 flex-col md:pl-0">
      <header class="sticky top-0 z-20 flex items-center justify-between border-b border-sales-border bg-sales-surface px-4 py-3 md:hidden">
        <button
          type="button"
          class="rounded-md p-2 text-sales-tertiary hover:bg-sales-muted"
          @click="isSidebarOpen = true"
          aria-label="Open menu"
        >
          <PhList :size="20" />
        </button>
        <p class="font-display text-sm font-semibold text-sales-tertiary">{{ pageTitle }}</p>
        <div class="h-9 w-9" />
      </header>

      <main class="flex-1 p-4 md:p-8">
        <router-view />
      </main>
    </div>
  </div>
</template>
