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
  <div class="min-h-screen flex items-center justify-center bg-[url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center px-4 font-sans relative overflow-hidden">
    <!-- Background Overlay -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>

    <!-- Login Card -->
    <div class="max-w-md w-full bg-[#0a0f1e]/80 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] md:rounded-[3rem] p-8 md:p-12 shadow-2xl relative z-10">
      <div class="text-center mb-10 md:mb-12">
        <div class="mb-6 transform hover:rotate-6 transition-transform">
           <img src="/favicon.png" class="w-14 h-14 md:w-16 md:h-16 mx-auto" alt="SGOplus Logo">
        </div>
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tighter drop-shadow-2xl">SGOplus <span class="text-blue-400">OS</span></h1>
        <p class="text-blue-100/40 mt-2 md:mt-3 font-bold uppercase tracking-[0.3em] text-[9px] md:text-[10px]">Secure Core Environment</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6 md:space-y-8">
        <div>
          <label class="block text-[9px] md:text-[10px] font-black text-white/30 uppercase mb-2 md:mb-3 tracking-widest ml-1">電子郵件 / Email</label>
          <input 
            v-model="email"
            type="email" 
            required 
            class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 md:py-4 text-white font-bold outline-none focus:border-blue-500/50 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder:text-white/10 text-sm md:text-base"
            placeholder="ADMIN@SGOPLUS.ONE"
          />
        </div>

        <div>
          <label class="block text-[9px] md:text-[10px] font-black text-white/30 uppercase mb-2 md:mb-3 tracking-widest ml-1">安全密碼 / Password</label>
          <input 
            v-model="password"
            type="password" 
            required 
            class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 md:py-4 text-white font-bold outline-none focus:border-blue-500/50 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder:text-white/10 text-sm md:text-base"
            placeholder="••••••••"
          />
        </div>

        <div v-if="error" class="text-red-400 text-[11px] md:text-xs font-bold bg-red-500/10 border border-red-500/20 p-4 rounded-xl animate-pulse">
          {{ error }}
        </div>

        <div class="flex flex-col space-y-3 md:space-y-4 pt-2">
            <button 
              type="submit" 
              :disabled="loading"
              class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-xl shadow-blue-600/30 active:scale-95 uppercase tracking-widest text-xs disabled:opacity-50 flex items-center justify-center space-x-2"
            >
              <span>{{ loading ? '認證中...' : '進入系統 / ACCESS' }}</span>
              <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3"></path></svg>
            </button>

            <a href="http://join.platform.sgo7.top/" target="_blank" 
               class="w-full bg-white/5 hover:bg-white/10 border border-white/10 text-white/40 hover:text-white font-bold py-3.5 rounded-2xl transition-all text-center text-[10px] md:text-xs uppercase tracking-widest active:scale-95">
               Join SGOplus Platform
            </a>
        </div>
      </form>
      
      <div class="mt-10 md:mt-12 text-center text-[8px] md:text-[9px] font-black text-white/10 uppercase tracking-[0.4em]">
        &copy; 2026 SGOplus Group
      </div>
    </div>

    <!-- Decorative Gradients -->
    <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-blue-600/10 rounded-full blur-[100px]"></div>
    <div class="absolute -top-20 -right-20 w-80 h-80 bg-purple-600/10 rounded-full blur-[100px]"></div>
  </div>
</template>

<style scoped>
input::placeholder {
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}
</style>
