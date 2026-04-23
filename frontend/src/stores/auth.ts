import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || '')
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const roles = ref<string[]>(JSON.parse(localStorage.getItem('roles') || '[]'))
  const permissions = ref<string[]>(JSON.parse(localStorage.getItem('permissions') || '[]'))

  const isAuthenticated = computed(() => !!token.value)

  function setAuth(authData: { token: string, user: any }) {
    token.value = authData.token
    user.value = authData.user
    roles.value = authData.user.roles || []
    permissions.value = authData.user.permissions || []

    localStorage.setItem('token', authData.token)
    localStorage.setItem('user', JSON.stringify(authData.user))
    localStorage.setItem('roles', JSON.stringify(roles.value))
    localStorage.setItem('permissions', JSON.stringify(permissions.value))
  }

  function logout() {
    token.value = ''
    user.value = null
    roles.value = []
    permissions.value = []
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('roles')
    localStorage.removeItem('permissions')
  }

  function hasPermission(permission: string) {
    return permissions.value.includes(permission) || roles.value.includes('SuperAdmin')
  }

  return { token, user, roles, permissions, isAuthenticated, setAuth, logout, hasPermission }
})
