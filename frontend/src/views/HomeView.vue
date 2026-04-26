<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

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
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-blue-600">Sistema de Vendas</h1>
        <button
          @click="handleLogout"
          class="px-6 py-2 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition"
        >
          Sair
        </button>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-12">
      <!-- Welcome Card -->
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-16 text-center">
          <h2 class="text-4xl font-bold text-white mb-2">Bem-vindo!</h2>
          <p class="text-blue-100 text-lg">Seu perfil de usuário</p>
        </div>
      </div>

      <!-- User Info Card -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- User Profile -->
        <div class="bg-white rounded-2xl shadow-lg p-8 border-l-4 border-blue-600">
          <div class="flex items-center mb-6">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-2xl text-white font-bold">
              {{ authStore.user?.name?.charAt(0).toUpperCase() }}
            </div>
            <div class="ml-6">
              <h3 class="text-3xl font-bold text-gray-800">{{ authStore.user?.name }}</h3>
            </div>
          </div>

          <div class="space-y-4">
            <div>
              <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Email</p>
              <p class="text-lg text-gray-800 break-all">{{ authStore.user?.email }}</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">ID do Usuário</p>
              <p class="text-lg text-gray-800">{{ authStore.user?.id }}</p>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="space-y-4">
          <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-8 text-white">
            <p class="text-sm font-semibold opacity-80 uppercase tracking-wide">Status</p>
            <p class="text-3xl font-bold">Autenticado</p>
            <p class="text-green-100 text-sm mt-2">✓ Acesso autorizado ao sistema</p>
          </div>

          <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-8 text-white">
            <p class="text-sm font-semibold opacity-80 uppercase tracking-wide">Funcionalidades</p>
            <p class="text-3xl font-bold">Em Desenvolvimento</p>
            <p class="text-purple-100 text-sm mt-2">🚀 Mais recursos em breve</p>
          </div>
        </div>
      </div>

      <!-- Actions Section -->
      <div class="mt-12 bg-white rounded-2xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Próximas Etapas</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                📦
              </div>
            </div>
            <div class="ml-4">
              <h4 class="text-lg font-semibold text-gray-800">Produtos</h4>
              <p class="text-gray-600 text-sm mt-1">Gerencie seu catálogo de produtos</p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-500 text-white">
                👥
              </div>
            </div>
            <div class="ml-4">
              <h4 class="text-lg font-semibold text-gray-800">Clientes</h4>
              <p class="text-gray-600 text-sm mt-1">Administre seus clientes</p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-500 text-white">
                💰
              </div>
            </div>
            <div class="ml-4">
              <h4 class="text-lg font-semibold text-gray-800">Vendas</h4>
              <p class="text-gray-600 text-sm mt-1">Registre e acompanhe suas vendas</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
