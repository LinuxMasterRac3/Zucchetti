import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { authApi, dashboardApi } from '@/services/api'
import router from '@/router'

export interface User {
  id: number
  nome?: string
  cognome?: string
  username: string
  ruolo: string
  max_prenotazioni: number
  coordinatore?: string | null
}

export interface DashboardData {
  profile: {
    id: number
    nome: string
    cognome: string
    username: string
    ruolo: string
    max_prenotazioni: number
    coordinatore: string | null
  }
  prenotazioni_attive: number
  prossime_prenotazioni: Array<{
    id: number
    data_prenotazione: string
    ora_inizio: string
    ora_fine: string
    codice_univoco: string
    codice_tipo: string
    tipo_descrizione: string
    modifiche_counter: number
  }>
  statistiche?: {
    utenti_per_ruolo: Record<string, number>
    assets_per_stato: Record<string, number>
    prenotazioni_oggi: number
  }
  team?: Array<{
    id: number
    nome: string
    cognome: string
    username: string
  }>
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const dashboard = ref<DashboardData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => user.value !== null)
  const isGestore = computed(() => user.value?.ruolo === 'gestore')
  const isCoordinatore = computed(() => user.value?.ruolo === 'coordinatore')
  const isDipendente = computed(() => user.value?.ruolo === 'dipendente')

  async function login(username: string, password: string) {
    loading.value = true
    error.value = null
    try {
      const response = await authApi.login(username, password)
      user.value = response.data.user
      await router.push('/dashboard')
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Errore di connessione'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function checkSession() {
    try {
      const response = await authApi.checkSession()
      if (response.data.authenticated) {
        user.value = response.data.user
      }
    } catch {
      user.value = null
    }
  }

  async function fetchDashboard() {
    try {
      const response = await dashboardApi.get()
      dashboard.value = response.data
      // Aggiorna profilo utente
      if (response.data.profile) {
        user.value = {
          ...user.value!,
          nome: response.data.profile.nome,
          cognome: response.data.profile.cognome,
          coordinatore: response.data.profile.coordinatore,
        }
      }
    } catch (err: any) {
      console.error('Errore caricamento dashboard:', err)
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } finally {
      user.value = null
      dashboard.value = null
      await router.push('/login')
    }
  }

  return {
    user,
    dashboard,
    loading,
    error,
    isAuthenticated,
    isGestore,
    isCoordinatore,
    isDipendente,
    login,
    checkSession,
    fetchDashboard,
    logout,
  }
})
