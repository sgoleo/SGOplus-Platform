<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const isMenuOpen = ref(false)
const showProfileModal = ref(false)

const passwordForm = ref({
  current_password: '',
  new_password: '',
  confirm_password: ''
})
const loading = ref(false)
const message = ref({ text: '', type: '' })

const handleLogout = () => {
  auth.logout()
  router.push('/login')
}

const handleChangePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
    message.value = { text: '新密碼與確認密碼不一致', type: 'error' }
    return
  }
  
  loading.value = true
  message.value = { text: '', type: '' }
  
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    const res = await axios.post('/api/user/change-password', {
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password
    }, config)
    
    message.value = { text: res.data.message, type: 'success' }
    passwordForm.value = { current_password: '', new_password: '', confirm_password: '' }
    setTimeout(() => { showProfileModal.value = false; message.value = { text: '', type: '' } }, 2000)
  } catch (err: any) {
    message.value = { text: err.response?.data?.message || '密碼更新失敗', type: 'error' }
  } finally {
    loading.value = false
  }
}

const navLinks = [
  { name: '任務公海', path: '/pool', icon: '🔥', highlight: true },
  { name: '儀表板', path: '/', icon: '📊' },
  { name: '審核中心', path: '/reviews', icon: '⚖️', adminOnly: true },
  { name: '專案管理', path: '/projects', icon: '📁', adminOnly: true },
  { name: '任務管理', path: '/tasks-management', icon: '📋', adminOnly: true },
  { name: '用戶管理', path: '/users', icon: '👥', adminOnly: true },
]
</script>

<template>
  <!-- Global Overlay (Portal-like behavior) -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="isMenuOpen" class="fixed inset-0 z-[998] bg-black/60 backdrop-blur-md lg:hidden" @click="isMenuOpen = false"></div>
    </Transition>
    
    <Transition name="slide-down">
      <div v-if="isMenuOpen" class="fixed top-0 left-0 right-0 z-[999] bg-[#0a0f1e]/95 backdrop-blur-3xl border-b border-white/10 p-6 pt-24 lg:hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-b-[2.5rem]">
        <!-- Close Button inside Menu -->
        <button @click="isMenuOpen = false" class="absolute top-6 right-6 bg-white/10 p-2 rounded-full text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round"></path></svg>
        </button>

        <div class="grid grid-cols-1 gap-3">
          <template v-for="link in navLinks" :key="link.path">
            <router-link 
              v-if="!link.adminOnly || auth.roles.includes('SuperAdmin')"
              :to="link.path" 
              @click="isMenuOpen = false"
              class="w-full px-6 py-4 rounded-2xl text-base font-black transition-all flex items-center justify-between"
              :class="[
                route.path === link.path 
                  ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/30' 
                  : 'bg-white/5 text-white/60 hover:text-white hover:bg-white/10'
              ]"
            >
              <div class="flex items-center">
                <span class="mr-3 text-xl">{{ link.icon }}</span>
                {{ link.name }}
              </div>
              <svg class="w-5 h-5 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3" stroke-linecap="round"></path></svg>
            </router-link>
          </template>
          
          <div class="border-t border-white/10 mt-4 pt-6 flex items-center justify-between px-2">
            <div class="flex items-center cursor-pointer group" @click="showProfileModal = true; isMenuOpen = false">
               <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-bold text-white mr-3 shadow-lg border border-white/20 group-hover:scale-110 transition-transform">
                 {{ auth.user?.name?.charAt(0).toUpperCase() }}
               </div>
               <div>
                 <p class="text-sm font-black text-white leading-tight group-hover:text-blue-400 transition-colors">{{ auth.user?.name }}</p>
                 <p class="text-[10px] text-white/40 uppercase tracking-widest mt-0.5">{{ auth.user?.department || 'Member' }}</p>
               </div>
            </div>
            <button @click="handleLogout" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-xl px-5 py-2.5 text-xs font-black uppercase transition-all">
              登出
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- User Profile & Password Modal -->
    <Transition name="modal">
      <div v-if="showProfileModal" class="fixed inset-0 z-[1000] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showProfileModal = false; message = {text:'', type:''}"></div>
        <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-md rounded-t-[2.5rem] sm:rounded-[2.5rem] p-8 md:p-10 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar text-white">
          <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
          
          <div class="flex items-center space-x-4 mb-10">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-3xl font-black shadow-2xl border border-white/20">
              {{ auth.user?.name?.charAt(0).toUpperCase() }}
            </div>
            <div>
              <h2 class="text-2xl font-black tracking-tight">{{ auth.user?.name }}</h2>
              <p class="text-blue-400 text-[10px] font-black uppercase tracking-widest">{{ auth.user?.department || 'System Member' }}</p>
            </div>
          </div>

          <h3 class="text-sm font-black uppercase tracking-[0.2em] text-white/30 mb-6 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="2.5"></path></svg>
            修改安全密碼
          </h3>

          <div v-if="message.text" :class="message.type === 'success' ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'" class="mb-6 p-4 rounded-xl border text-xs font-bold transition-all animate-fade-in">
            {{ message.text }}
          </div>

          <form @submit.prevent="handleChangePassword" class="space-y-5">
            <div>
              <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">目前密碼</label>
              <input v-model="passwordForm.current_password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold outline-none focus:border-blue-500/50 transition-all text-sm">
            </div>
            <div>
              <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">新密碼 (至少 6 位)</label>
              <input v-model="passwordForm.new_password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold outline-none focus:border-blue-500/50 transition-all text-sm">
            </div>
            <div>
              <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">確認新密碼</label>
              <input v-model="passwordForm.confirm_password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold outline-none focus:border-blue-500/50 transition-all text-sm">
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-6">
              <button type="button" @click="showProfileModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all text-xs uppercase">取消</button>
              <button type="submit" :disabled="loading" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-xl active:scale-95 text-xs uppercase tracking-widest disabled:opacity-50">
                {{ loading ? '更新中...' : '確認變更密碼' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Main Navbar -->
  <nav class="sticky top-0 z-[100] w-full bg-white/10 backdrop-blur-2xl border-b border-white/10 px-4 md:px-8 py-3 md:py-4 flex justify-between items-center shadow-2xl">
    <div class="flex items-center space-x-2 md:space-x-4 min-w-0 flex-shrink-0">
      <!-- Title container with overflow control instead of the whole nav -->
      <div class="overflow-hidden truncate max-w-[150px] xs:max-w-[200px] sm:max-w-none">
        <h2 @click="router.push('/')" class="text-lg md:text-2xl font-black text-white tracking-tighter drop-shadow-md cursor-pointer truncate">
          SGOplus <span class="text-blue-300">OS</span>
        </h2>
      </div>
      
      <!-- Desktop Nav -->
      <nav class="hidden lg:flex items-center space-x-1 ml-4 bg-black/20 p-1 rounded-xl border border-white/5">
        <template v-for="link in navLinks" :key="link.path">
          <router-link 
            v-if="!link.adminOnly || auth.roles.includes('SuperAdmin')"
            :to="link.path" 
            class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all flex items-center"
            :class="[
              route.path === link.path 
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' 
                : link.highlight 
                  ? 'text-orange-400 hover:text-orange-300 hover:bg-orange-500/10' 
                  : 'text-white/40 hover:text-white hover:bg-white/5'
            ]"
          >
            <span v-if="link.icon" class="mr-1.5">{{ link.icon }}</span>
            {{ link.name }}
          </router-link>
        </template>
      </nav>
      
      <span v-if="auth.roles.includes('SuperAdmin')" class="hidden sm:inline-block bg-purple-500/80 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter ml-2">
        ADMIN
      </span>
    </div>
    
    <div class="flex items-center space-x-2 md:space-x-6 text-white flex-shrink-0">
      <!-- Points Badge -->
      <div class="bg-white/10 backdrop-blur-md border border-yellow-500/30 px-3 py-1.5 rounded-2xl flex items-center space-x-1.5 shadow-lg flex-shrink-0">
        <span class="text-yellow-400 text-sm md:text-lg leading-none">✦</span>
        <span class="text-white font-black text-[10px] md:text-sm leading-none">{{ auth.user?.points || 0 }}</span>
        <span class="hidden xs:inline text-[8px] md:text-[10px] text-yellow-500/70 uppercase font-black">pts</span>
      </div>

      <div class="hidden sm:block text-right cursor-pointer group" @click="showProfileModal = true">
        <p class="text-xs md:text-sm font-black tracking-tight text-white group-hover:text-blue-400 transition-colors">{{ auth.user?.name }}</p>
        <p class="text-[9px] md:text-[10px] font-medium text-blue-100/70 uppercase tracking-widest">{{ auth.user?.department || 'System Wide' }}</p>
      </div>

      <!-- Mobile Menu Toggle -->
      <button @click="isMenuOpen = !isMenuOpen" class="lg:hidden bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-full p-2.5 transition shadow-lg active:scale-90">
        <svg v-if="!isMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16m-7 6h7" stroke-width="2.5" stroke-linecap="round"></path></svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round"></path></svg>
      </button>

      <!-- Desktop Logout -->
      <button @click="handleLogout" class="hidden lg:block bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-full p-2.5 transition shadow-lg active:scale-90">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-width="2.5"></path></svg>
      </button>
    </div>
  </nav>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-down-enter-from, .slide-down-leave-to { transform: translateY(-100%); opacity: 0; }

.modal-enter-active, .modal-leave-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.9) translateY(40px); }

@media (max-width: 640px) {
  .modal-enter-from, .modal-leave-to { transform: translateY(100%); opacity: 0; }
}

@media (max-width: 380px) {
  .xs\:inline { display: none; }
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}
</style>
