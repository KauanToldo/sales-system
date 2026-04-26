<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { PhEnvelopeSimple, PhEye, PhEyeSlash, PhLockSimple, PhUser } from '@phosphor-icons/vue'
import { toast } from 'vue3-toastify'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const fieldErrors = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
})

const nameHasError = computed(() => fieldErrors.name !== '')
const emailHasError = computed(() => fieldErrors.email !== '')
const passwordHasError = computed(() => fieldErrors.password !== '')
const confirmPasswordHasError = computed(() => fieldErrors.confirmPassword !== '')

const fieldLabelClass = 'mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-sales-ink'
const baseInputClass =
  'h-11 w-full rounded-[2px] border border-sales-border bg-sales-surface pl-10 pr-10 text-sm text-sales-tertiary outline-none transition placeholder:text-slate-300 focus:border-sales-primary focus:ring-2 focus:ring-sales-primary/15'
const errorInputClass = 'border-red-500 focus:border-red-500 focus:ring-red-500/15'
const iconClass = 'pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sales-ink'
const eyeButtonClass =
  'absolute right-2 top-1/2 -translate-y-1/2 rounded-sm p-1 text-sales-ink transition hover:bg-sales-neutral'

const clearFieldError = (field) => {
  fieldErrors[field] = ''
}

const validateForm = () => {
  fieldErrors.name = ''
  fieldErrors.email = ''
  fieldErrors.password = ''
  fieldErrors.confirmPassword = ''

  let isValid = true

  if (!name.value.trim()) {
    fieldErrors.name = 'Full name is required'
    isValid = false
  }

  if (!email.value.trim()) {
    fieldErrors.email = 'Email is required'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
    fieldErrors.email = 'Enter a valid email address'
    isValid = false
  }

  if (!password.value) {
    fieldErrors.password = 'Password is required'
    isValid = false
  } else if (password.value.length < 6) {
    fieldErrors.password = 'Password must be at least 6 characters'
    isValid = false
  }

  if (!confirmPassword.value) {
    fieldErrors.confirmPassword = 'Confirm your password'
    isValid = false
  } else if (password.value && confirmPassword.value !== password.value) {
    fieldErrors.confirmPassword = 'Passwords do not match'
    isValid = false
  }

  return isValid
}

const handleRegister = async () => {
  if (!validateForm()) {
    return
  }

  const success = await authStore.register(name.value, email.value, password.value, confirmPassword.value)

  if (success) {
    router.push({ name: 'dashboard' })
    return
  }

  if (authStore.error) {
    toast.error(authStore.error)
  }
}

const goToLogin = () => {
  router.push({ name: 'login' })
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-sales-neutral px-4 py-10">
    <section class="w-full max-w-[500px]">
      <div class="overflow-hidden rounded-[8px] border border-sales-border bg-sales-surface shadow-[0_8px_24px_rgba(17,24,39,0.08)]">
        <header class="px-7 pb-4 pt-8 text-center">
          <h1 class="text-[26px] font-bold leading-none text-sales-primary">SalesZuchetti</h1>
          <p class="mt-2 text-[12px] font-medium text-slate-500">Create your enterprise workspace account</p>
        </header>

        <form @submit.prevent="handleRegister" class="px-7 pb-7 pt-2">
          <div class="mb-5">
            <label for="name" :class="fieldLabelClass">Full Name</label>
            <div class="relative">
              <span :class="iconClass" aria-hidden="true">
                <PhUser :size="16" weight="regular" />
              </span>
              <input
                id="name"
                v-model="name"
                type="text"
                placeholder="João Silva"
                :class="[baseInputClass, nameHasError && errorInputClass]"
                @input="clearFieldError('name')"
              />
            </div>
            <p v-if="fieldErrors.name" class="mt-1 text-xs font-medium text-red-600">
              {{ fieldErrors.name }}
            </p>
          </div>

          <div class="mb-5">
            <label for="email" :class="fieldLabelClass">Email Address</label>
            <div class="relative">
              <span :class="iconClass" aria-hidden="true">
                <PhEnvelopeSimple :size="16" weight="regular" />
              </span>
              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="name@company.com"
                :class="[baseInputClass, emailHasError && errorInputClass]"
                @input="clearFieldError('email')"
              />
            </div>
            <p v-if="fieldErrors.email" class="mt-1 text-xs font-medium text-red-600">
              {{ fieldErrors.email }}
            </p>
          </div>

          <div class="mb-5">
            <label for="password" :class="fieldLabelClass">Password</label>
            <div class="relative">
              <span :class="iconClass" aria-hidden="true">
                <PhLockSimple :size="16" weight="regular" />
              </span>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                :class="[baseInputClass, passwordHasError && errorInputClass]"
                @input="clearFieldError('password')"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                :class="eyeButtonClass"
                aria-label="Toggle password visibility"
              >
                <PhEye v-if="showPassword" :size="16" weight="regular" />
                <PhEyeSlash v-else :size="16" weight="regular" />
              </button>
            </div>
            <p v-if="fieldErrors.password" class="mt-1 text-xs font-medium text-red-600">
              {{ fieldErrors.password }}
            </p>
          </div>

          <div class="mb-5">
            <label for="confirmPassword" :class="fieldLabelClass">Confirm Password</label>
            <div class="relative">
              <span :class="iconClass" aria-hidden="true">
                <PhLockSimple :size="16" weight="regular" />
              </span>
              <input
                id="confirmPassword"
                v-model="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="••••••••"
                :class="[baseInputClass, confirmPasswordHasError && errorInputClass]"
                @input="clearFieldError('confirmPassword')"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                :class="eyeButtonClass"
                aria-label="Toggle confirm password visibility"
              >
                <PhEye v-if="showConfirmPassword" :size="16" weight="regular" />
                <PhEyeSlash v-else :size="16" weight="regular" />
              </button>
            </div>
            <p v-if="fieldErrors.confirmPassword" class="mt-1 text-xs font-medium text-red-600">
              {{ fieldErrors.confirmPassword }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="authStore.isLoading"
            class="mt-1 h-11 w-full rounded-[2px] bg-sales-primary text-sm font-semibold text-white shadow-[0_1px_0_rgba(0,0,0,0.05)] transition hover:bg-[#0065a8] disabled:cursor-not-allowed disabled:opacity-60"
          >
            {{ authStore.isLoading ? 'Creating account...' : 'Create Account' }}
          </button>
        </form>

        <footer class="border-t border-sales-border px-7 py-4">
          <p class="text-center text-[12px] text-slate-500">
            Already have an account?
            <button
              type="button"
              @click="goToLogin"
              class="font-semibold text-sales-primary transition hover:text-[#005a93]"
            >
              Sign in
            </button>
          </p>
        </footer>
      </div>
    </section>
  </main>
</template>
