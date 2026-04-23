<script setup>
import { ref, onMounted } from 'vue'

const health = ref({ loading: true, status: null, error: null })

onMounted(async () => {
  try {
    const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    const res = await fetch(`${apiUrl}/health`)
    const data = await res.json()
    health.value = { loading: false, status: data.status, error: null }
  } catch (err) {
    health.value = { loading: false, status: null, error: 'health check failed' }
  }
})
</script>

<template>
  <p v-if="health.loading">Checking API health...</p>
  <p v-else-if="health.error">API: {{ health.error }}</p>
  <p v-else>API health: {{ health.status }}</p>
</template>
