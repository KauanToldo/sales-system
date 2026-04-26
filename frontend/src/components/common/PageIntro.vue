<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <section class="rounded-2xl border border-sales-border bg-sales-surface p-6 shadow-sm md:p-8">
    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-sales-primary">Sales Zuchetti</p>
    <h1 class="mt-2 font-display text-2xl font-bold text-sales-tertiary md:text-3xl">{{ title }}</h1>
    <p class="mt-3 max-w-2xl text-sm text-sales-ink md:text-base">
      {{ description }}
    </p>
  </section>
</template>
