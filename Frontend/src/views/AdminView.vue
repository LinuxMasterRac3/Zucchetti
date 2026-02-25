<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usersApi, assetsApi, bookingsApi, rolesApi } from '@/services/api'

// =====================================================================
// State
// =====================================================================
const activeTab = ref<'users' | 'assets' | 'bookings'>('users')

interface User {
  id: number; nome: string; cognome: string; username: string; id_ruolo: number; ruolo_nome: string; id_coordinatore: number | null
}
interface Asset {
  id: number; codice_univoco: string; id_tipo: number; codice_tipo: string; tipo_descrizione: string; stato_attuale: string
}
interface Booking {
  id: number; id_utente: number; codice_univoco: string; data_prenotazione: string;
  ora_inizio: string; ora_fine: string; stato_prenotazione: string;
  utente_nome: string; utente_cognome: string; username: string
}
interface Role { id: number; nome: string; descrizione: string; max_prenotazioni: number }

const users = ref<User[]>([])
const assets = ref<Asset[]>([])
const allBookings = ref<Booking[]>([])
const roles = ref<Role[]>([])
const loading = ref(false)
const msg = ref<{ type: 'ok' | 'err'; text: string } | null>(null)

// Forms
const showUserForm = ref(false)
const userForm = ref({ nome: '', cognome: '', username: '', password: '', id_ruolo: 3, id_coordinatore: null as number | null })
const editUserId = ref<number | null>(null)

// =====================================================================
// Loaders
// =====================================================================
async function loadAll() {
  loading.value = true
  try {
    const [u, a, b, r] = await Promise.all([
      usersApi.getAll(), assetsApi.getAll(), bookingsApi.getAll(), rolesApi.getAll()
    ])
    users.value = u.data
    assets.value = a.data
    allBookings.value = b.data
    roles.value = r.data
  } catch { /* ignore */ } finally { loading.value = false }
}

// =====================================================================
// User Management
// =====================================================================
function openNewUser() {
  editUserId.value = null
  userForm.value = { nome: '', cognome: '', username: '', password: '', id_ruolo: 3, id_coordinatore: null }
  showUserForm.value = true
}

function openEditUser(u: User) {
  editUserId.value = u.id
  userForm.value = { nome: u.nome, cognome: u.cognome, username: u.username, password: '', id_ruolo: u.id_ruolo, id_coordinatore: u.id_coordinatore }
  showUserForm.value = true
}

async function saveUser() {
  msg.value = null
  try {
    if (editUserId.value) {
      const data: Record<string, unknown> = { ...userForm.value }
      if (!data.password) delete data.password
      await usersApi.update(editUserId.value, data)
      msg.value = { type: 'ok', text: 'Utente aggiornato' }
    } else {
      await usersApi.create(userForm.value)
      msg.value = { type: 'ok', text: 'Utente creato' }
    }
    showUserForm.value = false
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore' }
  }
}

async function deleteUser(id: number) {
  if (!confirm('Eliminare questo utente?')) return
  try {
    await usersApi.delete(id)
    msg.value = { type: 'ok', text: 'Utente eliminato' }
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore' }
  }
}

// =====================================================================
// Asset Management
// =====================================================================
async function toggleAssetStatus(asset: Asset) {
  const newStatus = asset.stato_attuale === 'non_prenotabile' ? 'disponibile' : 'non_prenotabile'
  try {
    await assetsApi.update(asset.id, { stato_attuale: newStatus })
    msg.value = { type: 'ok', text: `Asset ${asset.codice_univoco} → ${newStatus}` }
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore' }
  }
}

// =====================================================================
// Booking Revoke
// =====================================================================
async function revokeBooking(id: number) {
  if (!confirm('Revocare questa prenotazione?')) return
  try {
    await bookingsApi.cancel(id)
    msg.value = { type: 'ok', text: 'Prenotazione revocata' }
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore' }
  }
}

function getRoleBadge(nome: string) {
  switch (nome) {
    case 'gestore': return 'bg-red-500/20 text-red-300'
    case 'coordinatore': return 'bg-amber-500/20 text-amber-300'
    default: return 'bg-sky-500/20 text-sky-300'
  }
}

onMounted(loadAll)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-100">⚙️ Amministrazione</h1>
      <p class="text-gray-500 text-sm mt-1">Gestione utenti, asset e prenotazioni</p>
    </div>

    <!-- Messages -->
    <div v-if="msg" class="mb-4 px-4 py-3 rounded-xl text-sm"
      :class="msg.type === 'ok' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-red-500/10 border border-red-500/20 text-red-400'">
      {{ msg.text }}
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-gray-900/60 rounded-xl p-1 border border-gray-800/50 w-fit">
      <button
        v-for="tab in [{ key: 'users', label: '👥 Utenti' }, { key: 'assets', label: '🏢 Asset' }, { key: 'bookings', label: '📋 Prenotazioni' }] as const"
        :key="tab.key"
        @click="activeTab = tab.key"
        class="px-4 py-2 text-sm font-medium rounded-lg transition-all cursor-pointer"
        :class="activeTab === tab.key ? 'bg-sky-500/20 text-sky-400' : 'text-gray-500 hover:text-gray-300'"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="text-center py-20 text-gray-500 animate-pulse">Caricamento...</div>

    <!-- ========== USERS TAB ========== -->
    <div v-else-if="activeTab === 'users'">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-200">Utenti ({{ users.length }})</h2>
        <button @click="openNewUser" class="px-4 py-2 bg-sky-500/20 text-sky-400 rounded-lg text-sm font-medium hover:bg-sky-500/30 transition-colors cursor-pointer">
          + Nuovo Utente
        </button>
      </div>

      <!-- User Form -->
      <div v-if="showUserForm" class="bg-gray-900/60 rounded-2xl border border-gray-800/50 p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-300 mb-4">{{ editUserId ? 'Modifica' : 'Nuovo' }} Utente</h3>
        <div class="grid grid-cols-2 gap-4">
          <input v-model="userForm.nome" placeholder="Nome" class="px-4 py-2.5 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50" />
          <input v-model="userForm.cognome" placeholder="Cognome" class="px-4 py-2.5 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50" />
          <input v-model="userForm.username" placeholder="Username" class="px-4 py-2.5 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50" />
          <input v-model="userForm.password" type="password" :placeholder="editUserId ? 'Password (lascia vuoto se invariata)' : 'Password'" class="px-4 py-2.5 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50" />
          <select v-model="userForm.id_ruolo" class="px-4 py-2.5 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50">
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.nome }}</option>
          </select>
          <select v-model="userForm.id_coordinatore" class="px-4 py-2.5 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50">
            <option :value="null">Nessun coordinatore</option>
            <option v-for="u in users.filter(u => u.ruolo_nome === 'coordinatore')" :key="u.id" :value="u.id">{{ u.nome }} {{ u.cognome }}</option>
          </select>
        </div>
        <div class="flex gap-2 mt-4 justify-end">
          <button @click="showUserForm = false" class="px-4 py-2 text-sm text-gray-400 hover:text-gray-200 cursor-pointer">Annulla</button>
          <button @click="saveUser" class="px-4 py-2 bg-sky-500 text-white text-sm font-medium rounded-lg hover:bg-sky-400 transition-colors cursor-pointer">Salva</button>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-gray-900/60 rounded-2xl border border-gray-800/50 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-800/50">
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Utente</th>
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Username</th>
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Ruolo</th>
              <th class="text-right px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Azioni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id" class="border-b border-gray-800/30 hover:bg-gray-800/20">
              <td class="px-5 py-3 text-gray-200">{{ u.nome }} {{ u.cognome }}</td>
              <td class="px-5 py-3 text-gray-400">@{{ u.username }}</td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full" :class="getRoleBadge(u.ruolo_nome)">{{ u.ruolo_nome }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <button @click="openEditUser(u)" class="text-sky-400 hover:text-sky-300 mr-3 text-xs cursor-pointer">✏️</button>
                <button @click="deleteUser(u.id)" class="text-red-400 hover:text-red-300 text-xs cursor-pointer">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== ASSETS TAB ========== -->
    <div v-else-if="activeTab === 'assets'">
      <h2 class="text-lg font-semibold text-gray-200 mb-4">Asset ({{ assets.length }})</h2>
      <div class="bg-gray-900/60 rounded-2xl border border-gray-800/50 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-800/50">
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Codice</th>
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Tipo</th>
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Stato</th>
              <th class="text-right px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Azioni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in assets" :key="a.id" class="border-b border-gray-800/30 hover:bg-gray-800/20">
              <td class="px-5 py-3 text-gray-200 font-mono text-xs">{{ a.codice_univoco }}</td>
              <td class="px-5 py-3 text-gray-400 text-xs">{{ a.codice_tipo }} – {{ a.tipo_descrizione }}</td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full"
                  :class="a.stato_attuale === 'disponibile' ? 'bg-emerald-500/20 text-emerald-400' : a.stato_attuale === 'occupato' ? 'bg-red-500/20 text-red-400' : 'bg-gray-600/20 text-gray-400'">
                  {{ a.stato_attuale }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <button @click="toggleAssetStatus(a)" class="text-xs cursor-pointer"
                  :class="a.stato_attuale === 'non_prenotabile' ? 'text-emerald-400 hover:text-emerald-300' : 'text-amber-400 hover:text-amber-300'">
                  {{ a.stato_attuale === 'non_prenotabile' ? '✅ Abilita' : '🚫 Disabilita' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== BOOKINGS TAB ========== -->
    <div v-else-if="activeTab === 'bookings'">
      <h2 class="text-lg font-semibold text-gray-200 mb-4">Tutte le Prenotazioni ({{ allBookings.length }})</h2>
      <div class="bg-gray-900/60 rounded-2xl border border-gray-800/50 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-800/50">
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Asset</th>
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Utente</th>
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Data / Ora</th>
              <th class="text-left px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Stato</th>
              <th class="text-right px-5 py-3 text-xs text-gray-500 uppercase tracking-wider">Azioni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in allBookings" :key="b.id" class="border-b border-gray-800/30 hover:bg-gray-800/20">
              <td class="px-5 py-3 text-gray-200 font-mono text-xs">{{ b.codice_univoco }}</td>
              <td class="px-5 py-3 text-gray-400 text-xs">{{ b.utente_nome }} {{ b.utente_cognome }}</td>
              <td class="px-5 py-3 text-gray-400 text-xs">{{ b.data_prenotazione }} {{ b.ora_inizio?.substring(0, 5) }}–{{ b.ora_fine?.substring(0, 5) }}</td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full"
                  :class="b.stato_prenotazione === 'attiva' ? 'bg-emerald-500/20 text-emerald-400' : b.stato_prenotazione === 'revocata' ? 'bg-red-500/20 text-red-400' : 'bg-gray-500/20 text-gray-400'">
                  {{ b.stato_prenotazione }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <button
                  v-if="b.stato_prenotazione === 'attiva'"
                  @click="revokeBooking(b.id)"
                  class="text-red-400 hover:text-red-300 text-xs cursor-pointer"
                >
                  🚫 Revoca
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
