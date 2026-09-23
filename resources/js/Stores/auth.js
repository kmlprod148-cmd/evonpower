import { defineStore } from 'pinia'
import { router } from '@inertiajs/vue3'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    permissions: [],
    roles: [],
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    hasPermission: (state) => (permission) => {
      return state.permissions.includes(permission)
    },
    hasRole: (state) => (role) => {
      return state.roles.includes(role)
    },
    isAdmin: (state) => {
      return state.roles.includes('admin')
    },
    isIntegrator: (state) => {
      return state.roles.includes('integrator')
    },
    isPartner: (state) => {
      return state.roles.includes('partner')
    },
    isClient: (state) => {
      return state.roles.includes('client')
    },
  },

  actions: {
    setUser(user) {
      this.user = user
      if (user) {
        this.permissions = user.permissions || []
        this.roles = user.roles || []
      }
    },

    clearUser() {
      this.user = null
      this.permissions = []
      this.roles = []
    },

    logout() {
      router.post(route('logout'), {}, {
        onSuccess: () => {
          this.clearUser()
        },
      })
    },

    can(permission) {
      return this.hasPermission(permission)
    },

    canAny(permissions) {
      return permissions.some(permission => this.hasPermission(permission))
    },

    canAll(permissions) {
      return permissions.every(permission => this.hasPermission(permission))
    },
  },
})
