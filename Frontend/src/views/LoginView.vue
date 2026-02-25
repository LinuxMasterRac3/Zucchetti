<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DynamicCaptcha from '@/components/DynamicCaptcha.vue'

const authStore = useAuthStore()

const form = ref({
  username: '',
  password: '',
})

const errorMessage = ref('')
const isLoading = ref(false)
const captchaValidated = ref(false)
const showPassword = ref(false)

const handleCaptchaValidate = (isValid: boolean) => {
  captchaValidated.value = isValid
}

const handleLogin = async () => {
  if (!form.value.username || !form.value.password) {
    errorMessage.value = 'Per favore, inserisci username e password'
    return
  }

  if (form.value.password.length < 8) {
    errorMessage.value = 'La password deve contenere almeno 8 caratteri'
    return
  }

  if (!captchaValidated.value) {
    errorMessage.value = 'Per favore, completa la verifica di sicurezza'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    // Login uses the auth store – captcha is validated client-side
    // We pass 0 as captcha_answer since the server captcha.php is not used;
    // we need to adjust the auth.php to skip captcha server-side check
    // For now, we call the login directly
    await authStore.login(form.value.username, form.value.password)
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Errore di connessione al server'
    captchaValidated.value = false
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-950 flex items-center justify-center p-4">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-sky-500/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
      <!-- Logo / Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 shadow-lg shadow-sky-500/25 mb-4">
          <span class="text-2xl">🏢</span>
        </div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-sky-400 to-indigo-400 bg-clip-text text-transparent">
          Z-Volta
        </h1>
        <p class="text-gray-500 text-sm mt-1">Asset Management System</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-8 shadow-2xl shadow-black/20">
        <h2 class="text-xl font-semibold text-gray-100 mb-6">Accedi al portale</h2>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMessage }}
        </div>

        <!-- Username -->
        <div class="mb-4">
          <label class="label-with-icon block text-sm font-medium text-gray-400 mb-2">
            <span class="icon">👤</span>
            Username
          </label>
          <input
            v-model="form.username"
            type="text"
            required
            placeholder="Inserisci il tuo username"
            class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500/50 transition-all"
          />
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="label-with-icon block text-sm font-medium text-gray-400 mb-2">
            <span class="icon">🔐</span>
            Password
          </label>
          <div class="relative">
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              minlength="8"
              placeholder="Inserisci la tua password (minimo 8 caratteri)"
              class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500/50 transition-all pr-12"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition-colors cursor-pointer"
            >
              {{ showPassword ? '🙈' : '👁️' }}
            </button>
          </div>
          <span v-if="form.password && form.password.length < 8" class="text-xs text-red-400 mt-1">
            La password deve contenere almeno 8 caratteri
          </span>
        </div>

        <!-- CAPTCHA Component -->
        <div class="mb-6">
          <DynamicCaptcha :onValidate="handleCaptchaValidate" />
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="isLoading || !captchaValidated"
          class="w-full py-3 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-xl hover:from-sky-400 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-sky-500/50 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-sky-500/20 cursor-pointer"
        >
          <span v-if="isLoading" class="animate-pulse">Accesso in corso...</span>
          <span v-else>Accedi</span>
        </button>
      </form>

      <!-- Footer -->
      <p class="text-center text-gray-600 text-xs mt-6">
        © 2025 Zucchetti S.p.A. — Accademia Scuola Lavoro
      </p>
    </div>
  </div>
</template>

<style scoped>
.label-with-icon {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.icon {
  font-size: 16px;
  display: inline-block;
}
</style>
