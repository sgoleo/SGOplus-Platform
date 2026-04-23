<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const auth = useAuthStore()
const router = useRouter()

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await axios.post('/api/login', {
      email: email.value,
      password: password.value,
      device_name: 'frontend-web'
    })
    
    auth.setAuth(response.data)
    router.push('/')
  } catch (err: any) {
    error.value = err.response?.data?.message || '登入失敗，請檢查帳號密碼'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
      <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800">SGOplus Platform</h1>
        <p class="text-gray-500 mt-2">請登入您的帳號</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">電子郵件</label>
          <input 
            v-model="email"
            type="email" 
            required 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
            placeholder="admin@sgoplus.one"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">密碼</label>
          <input 
            v-model="password"
            type="password" 
            required 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
            placeholder="••••••••"
          />
        </div>

        <div v-if="error" class="text-red-500 text-sm bg-red-50 p-3 rounded-lg">
          {{ error }}
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition duration-200 disabled:opacity-50"
        >
          {{ loading ? '登入中...' : '立即登入' }}
        </button>
      </form>
      
      <div class="mt-6 text-center text-sm text-gray-400">
        &copy; 2026 SGOplus Group
      </div>
    </div>
  </div>
</template>
