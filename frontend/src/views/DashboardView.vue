<script setup>
import PageIntro from '../components/common/PageIntro.vue'
</script>

<template>
  <section class="space-y-6">
    <PageIntro
      title="Dashboard"
      description="Welcome to your workspace. This area is now structured to support the next modules with consistent navigation, visuals, and scalable components."
    />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article class="rounded-xl border border-sales-border bg-sales-surface p-5">
        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-sales-ink">Products</p>
        <h2 class="mt-2 font-display text-xl font-semibold text-sales-tertiary">Module ready</h2>
        <p class="mt-2 text-sm text-sales-ink">Navigation and page shell prepared for Product CRUD screens.</p>
      </article>

      <article class="rounded-xl border border-sales-border bg-sales-surface p-5">
        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-sales-ink">Customers</p>
        <h2 class="mt-2 font-display text-xl font-semibold text-sales-tertiary">Module ready</h2>
        <p class="mt-2 text-sm text-sales-ink">Customer routes and placeholders are available for implementation.</p>
      </article>

      <article class="rounded-xl border border-sales-border bg-sales-surface p-5">
        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-sales-ink">Payments</p>
        <h2 class="mt-2 font-display text-xl font-semibold text-sales-tertiary">Module ready</h2>
        <p class="mt-2 text-sm text-sales-ink">Payment methods and POS entry points are structured for next steps.</p>
      </article>
    </div>
  </section>
</template>
