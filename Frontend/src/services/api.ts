import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Per inviare i cookie di sessione PHP
})

// =====================================================================
// AUTH API
// =====================================================================
export const authApi = {
  login: (username: string, password: string) =>
    api.post('/auth.php', { username, password }),
  checkSession: () => api.get('/auth.php?action=check'),
  logout: () => api.post('/auth.php?action=logout'),
}

// =====================================================================
// DASHBOARD API
// =====================================================================
export const dashboardApi = {
  get: () => api.get('/dashboard.php'),
}

// =====================================================================
// ASSETS API
// =====================================================================
export const assetsApi = {
  getAll: (params?: { tipo?: string; stato?: string; data?: string }) =>
    api.get('/assets.php', { params }),
  getById: (id: number, data?: string) =>
    api.get(`/assets.php?id=${id}${data ? `&data=${data}` : ''}`),
  create: (asset: { codice_univoco: string; id_tipo: number; stato_attuale?: string }) =>
    api.post('/assets.php', asset),
  update: (id: number, data: Record<string, unknown>) =>
    api.put(`/assets.php?id=${id}`, data),
  delete: (id: number) => api.delete(`/assets.php?id=${id}`),
}

// =====================================================================
// BOOKINGS API
// =====================================================================
export const bookingsApi = {
  getAll: (params?: { user_id?: number; date?: string; asset_id?: number; stato?: string }) =>
    api.get('/bookings.php', { params }),
  getById: (id: number) => api.get(`/bookings.php?id=${id}`),
  create: (booking: {
    id_asset: number
    data_prenotazione: string
    ora_inizio: string
    ora_fine: string
    id_utente?: number
  }) => api.post('/bookings.php', booking),
  update: (id: number, data: Record<string, unknown>) =>
    api.put(`/bookings.php?id=${id}`, data),
  cancel: (id: number) => api.delete(`/bookings.php?id=${id}`),
}

// =====================================================================
// USERS API
// =====================================================================
export const usersApi = {
  getAll: () => api.get('/users.php'),
  getById: (id: number) => api.get(`/users.php?id=${id}`),
  create: (user: {
    nome: string
    cognome: string
    username: string
    password: string
    id_ruolo: number
    id_coordinatore?: number
  }) => api.post('/users.php', user),
  update: (id: number, data: Record<string, unknown>) =>
    api.put(`/users.php?id=${id}`, data),
  delete: (id: number) => api.delete(`/users.php?id=${id}`),
}

// =====================================================================
// ROLES API
// =====================================================================
export const rolesApi = {
  getAll: () => api.get('/roles.php'),
  getById: (id: number) => api.get(`/roles.php?id=${id}`),
}

// =====================================================================
// ASSET TYPES API
// =====================================================================
export const typesApi = {
  getAll: () => api.get('/types.php'),
}

export default api
