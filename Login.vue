<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="flex justify-center mb-6">
          <img src="../assets/zbook_box_logo.png" alt="Logo Z-Book" width="200" height="50">
        </div>
        <p>Accedi al tuo account</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="username" class="label-with-icon">
            <span class="icon">👤</span>
            Username
          </label>
          <input
            id="username"
            v-model="form.username"
            type="text"
            placeholder="Inserisci il tuo username"
            required
            @keyup.enter="handleLogin"
          />
        </div>

        <div class="form-group">
          <label for="password" class="label-with-icon">
            <span class="icon">🔐</span>
            Password
          </label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            placeholder="Inserisci la tua password (minimo 8 caratteri)"
            minlength="8"
            required
            @keyup.enter="handleLogin"
          />
          <span v-if="form.password && form.password.length < 8" class="error-text">
            La password deve contenere almeno 8 caratteri
          </span>
        </div>

        <DynamicCaptcha :onValidate="handleCaptchaValidate" />

        <button type="submit" class="btn-login" :disabled="isLoading || !captchaValidated">
          <span v-if="!isLoading">Accedi</span>
          <span v-else>Accesso in corso...</span>
        </button>
      </form>

      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>

      <div class="login-footer">
        <p>
          <router-link to="/register" class="link">Non hai un account? Registrati</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import DynamicCaptcha from '@/components/DynamicCaptcha.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({ username: '', password: '' })
const errorMessage = ref('')
const isLoading = ref(false)
const captchaValidated = ref(false)

const handleCaptchaValidate = (isValid: boolean) => { captchaValidated.value = isValid }

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
    const result = await authStore.login(form.value.username, form.value.password)
    if (result.success) setTimeout(() => router.push('/'), 500)
    else {
      errorMessage.value = result.message || 'Errore durante il login'
      captchaValidated.value = false
    }
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Errore di connessione al server'
  } finally { isLoading.value = false }
}
</script>

<style scoped>
/* Container */
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #1c1c1c; /* sfondo grigio scuro */
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Card trasparente */
.login-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  padding: 2rem;
  border-radius: 15px;
  width: 100%;
  max-width: 400px;
  border: 1px solid rgba(255, 69, 0, 0.5);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
}

/* Header */
.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.login-header p {
  margin-top: 0.5rem;
  color: #ccc;
  font-size: 16px;
}

/* Form */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.label-with-icon {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #eee;
}

.icon { font-size: 18px; }

.form-group input {
  padding: 0.75rem;
  border: 2px solid rgba(255, 69, 0, 0.5);
  border-radius: 8px;
  font-size: 14px;
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
  font-family: inherit;
}

.form-group input::placeholder { color: #aaa; }

.form-group input:focus {
  outline: none;
  border-color: #FF4500;
  box-shadow: 0 0 10px rgba(255, 69, 0, 0.5);
}

/* Pulsante */
.btn-login {
  padding: 0.75rem;
  background: linear-gradient(135deg, #FF4500 0%, #FF6347 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(255, 69, 0, 0.5);
}

.btn-login:disabled { opacity: 0.7; cursor: not-allowed; }

/* Errori */
.error-message {
  padding: 0.75rem;
  background-color: rgba(255, 69, 0, 0.1);
  color: #FF4500;
  border: 1px solid rgba(255, 69, 0, 0.3);
  border-radius: 5px;
  font-size: 14px;
  text-align: center;
}

.error-text { font-size: 12px; color: #FF6347; margin-top: -0.25rem; }

/* Footer */
.login-footer { text-align: center; margin-top: 1.5rem; font-size: 14px; }
.link { color: #FF4500; text-decoration: none; font-weight: 600; }
.link:hover { color: #FF6347; }

@media (max-width: 480px) {
  .login-card { margin: 1rem; }
}
</style>