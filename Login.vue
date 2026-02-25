<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1>Z-Book Login</h1>
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

const form = ref({
  username: '',
  password: ''
})

const errorMessage = ref('')
const isLoading = ref(false)
const captchaValidated = ref(false)

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
    const result = await authStore.login(form.value.username, form.value.password)

    if (result.success) {
      // Redirect to dashboard or home page
      setTimeout(() => {
        router.push('/')
      }, 500)
    } else {
      errorMessage.value = result.message || 'Errore durante il login'
      captchaValidated.value = false
    }
  } catch (error) {
    console.error('Login error:', error)
    errorMessage.value = 'Errore di connessione al server'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #f7f7f7 0%);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.login-card {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 400px;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.login-header h1 {
  margin: 0;
  color: #fc7922;
  font-size: 28px;
  font-weight: 700;
}

.login-header p {
  margin: 0.5rem 0 0 0;
  color: #666;
  font-size: 18px;
}

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

.form-group label {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.label-with-icon {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.icon {
  font-size: 18px;
  display: inline-block;
}

.form-group input {
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 5px;
  font-size: 14px;
  transition: border-color 0.3s ease;
  font-family: inherit;
}

.form-group input:focus {
  outline: none;
  border-color: #fc7922;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-login {
  padding: 0.75rem;
  background: linear-gradient(135deg, #fc7922 0%);
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  font-family: inherit;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error-message {
  padding: 0.75rem;
  background-color: #fee;
  color: #c33;
  border: 1px solid #fcc;
  border-radius: 5px;
  font-size: 14px;
  text-align: center;
}

.error-text {
  font-size: 12px;
  color: #c33;
  margin-top: -0.25rem;
}

.login-footer {
  text-align: center;
  margin-top: 1.5rem;
  font-size: 14px;
}

.login-footer p {
  margin: 0;
}

.link {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s ease;
}

.link:hover {
  color: #764ba2;
}

@media (max-width: 480px) {
  .login-card {
    margin: 1rem;
  }

  .login-header h1 {
    font-size: 24px;
  }
}
</style>
