<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const authStore = useAuthStore()
const route = useRoute()

const navItems = computed(() => {
  const items = [
    { path: '/dashboard', label: 'Dashboard', icon: '📊' },
    { path: '/mappa', label: 'Mappa Sede', icon: '🏢' },
  ]
  items.push({ path: '/prenotazioni', label: 'Prenotazioni', icon: '📋' })
  if (authStore.isGestore) {
    items.push({ path: '/admin', label: 'Amministrazione', icon: '⚙️' })
  }
  return items
})

const roleBadgeClass = computed(() => {
  switch (authStore.user?.ruolo) {
    case 'gestore': return 'bg-red-500/20 text-red-300 border-red-500/30'
    case 'coordinatore': return 'bg-amber-500/20 text-amber-300 border-amber-500/30'
    default: return 'bg-sky-500/20 text-sky-300 border-sky-500/30'
  }
})
</script>

<template>
  <div class="flex h-screen bg-gray-950">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900/80 backdrop-blur-xl border-r border-gray-800/50 flex flex-col">
      <!-- Logo -->
      <div class="p-6 border-b border-gray-800/50">
        <h1 class="text-xl font-bold bg-gradient-to-r from-sky-400 to-indigo-400 bg-clip-text text-transparent">
          Z-Volta
        </h1>
        <p class="text-[11px] text-gray-500 mt-1 tracking-wider uppercase">Asset Management</p>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-1">
        <RouterLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200"
          :class="route.path === item.path
            ? 'bg-sky-500/10 text-sky-400 shadow-lg shadow-sky-500/5'
            : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'"
        >
          <span class="text-lg">{{ item.icon }}</span>
          {{ item.label }}
        </RouterLink>
      </nav>

      <!-- User Info -->
      <div class="p-4 border-t border-gray-800/50">
        <div class="bg-gray-800/40 rounded-xl p-4">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
              {{ authStore.user?.username?.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-200 truncate">{{ authStore.user?.username }}</p>
              <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border" :class="roleBadgeClass">
                {{ authStore.user?.ruolo }}
              </span>
            </div>
          </div>
          <button
            @click="authStore.logout()"
            class="w-full px-4 py-2 text-sm text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all duration-200 cursor-pointer"
          >
            🚪 Logout
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto">
      <div class="p-8">
        <slot />
      </div>
    </main>
  </div>
</template>
