<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

onMounted(async () => {
  await authStore.fetchDashboard()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-100">Dashboard</h1>
      <p class="text-gray-500 text-sm mt-1">Benvenuto nel portale Z-Volta Asset Management</p>
    </div>

    <!-- User Profile Card -->
    <div v-if="authStore.dashboard" class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-6 mb-6">
      <div class="flex items-center gap-4 mb-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-sky-500/20">
          {{ authStore.dashboard.profile.nome?.charAt(0) }}{{ authStore.dashboard.profile.cognome?.charAt(0) }}
        </div>
        <div>
          <h2 class="text-lg font-semibold text-gray-100">
            {{ authStore.dashboard.profile.nome }} {{ authStore.dashboard.profile.cognome }}
          </h2>
          <p class="text-gray-500 text-sm">@{{ authStore.dashboard.profile.username }}</p>
        </div>
        <div class="ml-auto text-right">
          <span class="inline-block px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full border"
            :class="{
              'bg-red-500/20 text-red-300 border-red-500/30': authStore.dashboard.profile.ruolo === 'gestore',
              'bg-amber-500/20 text-amber-300 border-amber-500/30': authStore.dashboard.profile.ruolo === 'coordinatore',
              'bg-sky-500/20 text-sky-300 border-sky-500/30': authStore.dashboard.profile.ruolo === 'dipendente',
            }">
            {{ authStore.dashboard.profile.ruolo }}
          </span>
          <p v-if="authStore.dashboard.profile.coordinatore" class="text-gray-500 text-xs mt-1">
            Coordinatore: {{ authStore.dashboard.profile.coordinatore }}
          </p>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
      <div class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-5">
        <p class="text-gray-500 text-xs uppercase tracking-wider mb-1">Prenotazioni Attive</p>
        <p class="text-3xl font-bold text-sky-400">{{ authStore.dashboard?.prenotazioni_attive ?? '—' }}</p>
        <p class="text-gray-600 text-xs mt-1">
          Max: {{ authStore.dashboard?.profile.max_prenotazioni ?? '?' }}
        </p>
      </div>

      <!-- Gestore-only stats -->
      <template v-if="authStore.isGestore && authStore.dashboard?.statistiche">
        <div class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-5">
          <p class="text-gray-500 text-xs uppercase tracking-wider mb-1">Prenotazioni Oggi</p>
          <p class="text-3xl font-bold text-emerald-400">{{ authStore.dashboard.statistiche.prenotazioni_oggi }}</p>
        </div>
        <div class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-5">
          <p class="text-gray-500 text-xs uppercase tracking-wider mb-1">Utenti Totali</p>
          <p class="text-3xl font-bold text-indigo-400">
            {{ Object.values(authStore.dashboard.statistiche.utenti_per_ruolo).reduce((a: number, b: number) => a + b, 0) }}
          </p>
          <div class="flex gap-2 mt-1">
            <span v-for="(count, role) in authStore.dashboard.statistiche.utenti_per_ruolo" :key="role" class="text-gray-600 text-xs">
              {{ role }}: {{ count }}
            </span>
          </div>
        </div>
      </template>

      <!-- Coordinatore team info -->
      <template v-if="authStore.isCoordinatore && authStore.dashboard?.team">
        <div class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-5">
          <p class="text-gray-500 text-xs uppercase tracking-wider mb-1">Il Tuo Team</p>
          <p class="text-3xl font-bold text-amber-400">{{ authStore.dashboard.team.length }}</p>
          <p class="text-gray-600 text-xs mt-1">dipendenti</p>
        </div>
      </template>
    </div>

    <!-- Upcoming Bookings -->
    <div class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-6">
      <h3 class="text-lg font-semibold text-gray-200 mb-4">📅 Prossime Prenotazioni</h3>

      <div v-if="authStore.dashboard?.prossime_prenotazioni?.length === 0" class="text-center py-8 text-gray-600">
        <p class="text-4xl mb-2">📭</p>
        <p>Nessuna prenotazione attiva</p>
        <RouterLink to="/mappa" class="inline-block mt-3 px-4 py-2 bg-sky-500/10 text-sky-400 rounded-xl text-sm font-medium hover:bg-sky-500/20 transition-colors">
          Prenota un asset →
        </RouterLink>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="booking in authStore.dashboard?.prossime_prenotazioni"
          :key="booking.id"
          class="flex items-center gap-4 bg-gray-800/30 rounded-xl p-4 hover:bg-gray-800/50 transition-colors"
        >
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold"
            :class="{
              'bg-sky-500/10 text-sky-400': booking.codice_tipo === 'A',
              'bg-indigo-500/10 text-indigo-400': booking.codice_tipo === 'A2',
              'bg-emerald-500/10 text-emerald-400': booking.codice_tipo === 'B',
              'bg-amber-500/10 text-amber-400': booking.codice_tipo === 'C',
            }">
            {{ booking.codice_tipo }}
          </div>
          <div class="flex-1">
            <p class="text-sm font-semibold text-gray-200">{{ booking.codice_univoco }}</p>
            <p class="text-xs text-gray-500">{{ booking.tipo_descrizione }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-medium text-gray-300">{{ booking.data_prenotazione }}</p>
            <p class="text-xs text-gray-500">{{ booking.ora_inizio }} – {{ booking.ora_fine }}</p>
          </div>
          <div class="text-xs text-gray-600">
            Mod: {{ booking.modifiche_counter }}/2
          </div>
        </div>
      </div>
    </div>

    <!-- Team Members (coordinatore) -->
    <div v-if="authStore.isCoordinatore && authStore.dashboard?.team?.length" class="mt-6 bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-6">
      <h3 class="text-lg font-semibold text-gray-200 mb-4">👥 Il Tuo Team</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div v-for="member in authStore.dashboard.team" :key="member.id" class="flex items-center gap-3 bg-gray-800/30 rounded-xl p-3">
          <div class="w-8 h-8 rounded-full bg-sky-500/20 flex items-center justify-center text-sky-400 text-sm font-bold">
            {{ member.nome.charAt(0) }}
          </div>
          <div>
            <p class="text-sm font-medium text-gray-200">{{ member.nome }} {{ member.cognome }}</p>
            <p class="text-xs text-gray-500">@{{ member.username }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
