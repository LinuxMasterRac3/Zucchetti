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
  <div class="min-h-screen bg-black flex items-center justify-center p-4 relative">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-black to-[#FF4500]/30 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-black to-[#FF4500]/30 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
      <!-- Logo / Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-6">
          <img src="../assets/zbook_box_logo.png" alt="Logo Z-Book" width="200" height="50">
        </div>
        <p class="text-gray-400 text-sm mt-1">Asset Management System</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="bg-gray-500/10 backdrop-blur-xl rounded-2xl border border-gray-400/20 p-8 shadow-2xl shadow-black/10">
        <h2 class="text-xl font-semibold text-gray-100 mb-6">Accedi al portale</h2>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMessage }}
        </div>

        <!-- Username -->
        <div class="mb-4">
          <label class="label-with-icon block text-sm font-medium text-gray-300 mb-2">
            <span class="icon"></span>
            Username
          </label>
          <input
            v-model="form.username"
            type="text"
            required
            placeholder="Inserire username"
            class="w-full px-4 py-3 bg-gray-700/20 border border-gray-600/20 rounded-xl text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF4500]/50 focus:border-[#FF4500]/50 transition-all"
          />
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="label-with-icon block text-sm font-medium text-gray-300 mb-2">
            <span class="icon"></span>
            Password
          </label>
          <div class="relative">
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              minlength="8"
              placeholder="Inserire password"
              class="w-full px-4 py-3 bg-gray-700/20 border border-gray-600/20 rounded-xl text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF4500]/50 focus:border-[#FF4500]/50 transition-all pr-12"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-200 transition-colors cursor-pointer"
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
          class="w-full py-3 bg-gradient-to-r from-[#FF4500] to-[#FF6347] text-white font-semibold rounded-xl hover:from-[#FF6347] hover:to-[#FF4500] focus:outline-none focus:ring-2 focus:ring-[#FF4500]/50 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-[#FF4500]/20 cursor-pointer"
        >
          <span v-if="isLoading" class="animate-pulse">Accesso in corso...</span>
          <span v-else>Accedi</span>
        </button>
      </form>

      <!-- Footer -->
      <p class="text-center text-gray-500 text-xs mt-6">
        © 2025 Z-volta S.p.A. — Z-book
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