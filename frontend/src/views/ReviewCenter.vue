<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

const auth = useAuthStore()
const router = useRouter()
const pendingTasks = ref<any[]>([])
const loading = ref(true)

// Modal State
const showReviewModal = ref(false)
const selectedTask = ref<any>(null)

const fetchData = async () => {
  loading.value = true
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    const res = await axios.get('/api/tasks/pending-reviews', config)
    const rawData = res.data
    pendingTasks.value = Array.isArray(rawData) ? rawData : (rawData.value || rawData.data || [])
  } catch (err) {
    console.error('Failed to fetch pending reviews', err)
  } finally {
    loading.value = false
  }
}

const openReviewModal = (task: any) => {
  selectedTask.value = task
  showReviewModal.value = true
}

const handleApprove = async (userId: number) => {
  if (!selectedTask.value) return;
  try {
    await axios.post(`/api/tasks/${selectedTask.value.id}/approve`, {
      user_id: userId
    }, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    
    // 檢查是否還有其他人待審核，如果沒了就自動關閉並刷新
    const stillPending = selectedTask.value.assignees.filter((a: any) => a.id !== userId && a.pivot_status === 'pending_review')
    if (stillPending.length === 0) {
      showReviewModal.value = false
      selectedTask.value = null
    } else {
      // 局部更新本地狀態以維持 Modal 開啟
      selectedTask.value.assignees = stillPending
    }
    fetchData()
  } catch (err) { alert('審核通過操作失敗') }
}

const handleReject = async (userId: number) => {
  if (!selectedTask.value) return;
  try {
    await axios.post(`/api/tasks/${selectedTask.value.id}/reject`, {
      user_id: userId
    }, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    
    const stillPending = selectedTask.value.assignees.filter((a: any) => a.id !== userId && a.pivot_status === 'pending_review')
    if (stillPending.length === 0) {
      showReviewModal.value = false
      selectedTask.value = null
    } else {
      selectedTask.value.assignees = stillPending
    }
    fetchData()
  } catch (err) { alert('退回操作失敗') }
}

const handleLogout = () => { auth.logout(); router.push('/login') }

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed text-white font-sans">
    <div class="min-h-screen backdrop-blur-sm bg-black/10">
      
      <!-- Header (Consistent SGOplus OS Navbar) -->
      <nav class="sticky top-0 z-40 bg-white/10 backdrop-blur-xl border-b border-white/10 px-8 py-4 flex justify-between items-center shadow-xl">
        <div class="flex items-center space-x-4">
          <h2 class="text-2xl font-extrabold text-white tracking-tight drop-shadow-md">SGOplus <span class="text-blue-300">OS</span></h2>
          <nav class="flex items-center space-x-1 ml-4 bg-black/20 p-1 rounded-xl border border-white/5">
            <router-link to="/pool" class="px-4 py-1.5 rounded-lg text-sm font-black transition-all text-orange-400 hover:text-orange-300 hover:bg-orange-500/10 flex items-center">
              <span class="mr-1.5">🔥</span> 任務公海
            </router-link>
            <router-link to="/" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all text-white/40 hover:text-white hover:bg-white/5">
              儀表板
            </router-link>
            <router-link to="/reviews" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all bg-blue-600 text-white shadow-lg shadow-blue-600/20">
              審核中心
            </router-link>
            <router-link v-if="auth.roles.includes('SuperAdmin')" to="/projects" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all text-white/40 hover:text-white hover:bg-white/5">
              專案管理
            </router-link>
            <router-link v-if="auth.roles.includes('SuperAdmin')" to="/tasks-management" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all text-white/40 hover:text-white hover:bg-white/5">
              任務管理
            </router-link>
            <router-link v-if="auth.roles.includes('SuperAdmin')" to="/users" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all text-white/40 hover:text-white hover:bg-white/5">
              用戶管理
            </router-link>
          </nav>
          <span v-if="auth.roles.includes('SuperAdmin')" class="bg-purple-500/80 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter ml-2">ADMIN</span>
        </div>
        
        <div class="flex items-center space-x-6 text-white">
          <div class="bg-white/10 backdrop-blur-md border border-yellow-500/30 px-5 py-1.5 rounded-2xl flex items-center space-x-2 shadow-lg">
            <span class="text-yellow-400 text-xl">✦</span>
            <span class="text-white font-black text-sm">{{ auth.user?.points || 0 }} <span class="text-[10px] text-yellow-500/80 ml-0.5 uppercase">pts</span></span>
          </div>
          <div class="text-right">
            <p class="text-sm font-black tracking-tight">{{ auth.user?.name }}</p>
            <p class="text-[10px] font-medium text-blue-100/70 uppercase tracking-widest">系統最高權限</p>
          </div>
          <button @click="handleLogout" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-full p-2.5 transition shadow-lg active:scale-90"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-width="2.5"></path></svg></button>
        </div>
      </nav>

      <main class="p-8 max-w-[1600px] mx-auto">
        <header class="flex justify-between items-end mb-12">
          <div>
            <h1 class="text-5xl font-black mb-3 tracking-tighter drop-shadow-2xl">審核工作流</h1>
            <p class="text-blue-100/60 font-bold uppercase tracking-[0.3em] text-xs ml-1">集中處理全系統成員提交的開發成果與點數發放申請</p>
          </div>
          <div class="flex items-center space-x-4">
             <div class="bg-white/5 border border-white/10 px-6 py-3 rounded-2xl flex items-center space-x-3">
                <span class="text-xs font-black text-white/40 uppercase tracking-widest">待處理項</span>
                <span class="bg-blue-600 text-white font-black px-3 py-0.5 rounded-full text-sm shadow-lg">{{ pendingTasks.length }}</span>
             </div>
          </div>
        </header>

        <div v-if="loading" class="flex justify-center py-40"><div class="animate-spin rounded-full h-16 w-16 border-4 border-white/20 border-t-blue-500"></div></div>
        
        <!-- Pending List Table -->
        <div v-else class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-black/40">
          <div class="p-6 border-b border-white/5 bg-white/5 flex justify-between items-center">
            <h3 class="font-black text-white text-lg tracking-tighter uppercase ml-2">待審核任務總覽</h3>
          </div>

          <div class="overflow-x-auto"><table class="w-full text-left border-collapse"><thead><tr class="text-[10px] font-black uppercase tracking-[0.25em] text-white/30 border-b border-white/5"><th class="px-10 py-5">任務與專案資訊</th><th class="px-10 py-5">發布範圍</th><th class="px-10 py-5">獎勵點數</th><th class="px-10 py-5">申請人數</th><th class="px-10 py-5 text-right">管理操作</th></tr></thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="task in pendingTasks" :key="task.id" class="hover:bg-white/5 transition-all group">
                <td class="px-10 py-8">
                  <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-yellow-500/20 to-orange-600/20 flex items-center justify-center border border-yellow-500/20 shadow-lg text-yellow-400">
                      <span v-if="task.is_crowdsourced" class="text-xl">🔥</span>
                      <span v-else class="text-xl">✦</span>
                    </div>
                    <div>
                      <p class="font-black text-white text-lg group-hover:text-blue-300 transition-colors">{{ task.title }}</p>
                      <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">{{ task.project?.name }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-10 py-8">
                  <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg border" :class="task.department === 'Public' ? 'text-purple-300 bg-purple-500/10 border-purple-500/20' : 'text-blue-300 bg-blue-500/10 border-blue-500/20'">
                    {{ task.department }}
                  </span>
                </td>
                <td class="px-10 py-8">
                  <div class="flex items-center space-x-2 text-yellow-400 font-black">
                    <span class="text-xs">✦</span><span class="text-xl">{{ task.reward_points }}</span>
                  </div>
                </td>
                <td class="px-10 py-8">
                   <div class="flex items-center space-x-2">
                     <span class="text-sm font-black text-white/80">{{ task.assignees?.filter((a:any)=>a.pivot_status === 'pending_review').length }}</span>
                     <span class="text-[10px] font-bold text-white/20 uppercase tracking-tighter">位成員提交</span>
                   </div>
                </td>
                <td class="px-10 py-8 text-right">
                  <button @click="openReviewModal(task)" class="bg-blue-600 hover:bg-blue-500 text-white font-black text-[10px] px-8 py-3 rounded-xl transition-all shadow-lg shadow-blue-600/20 uppercase tracking-widest active:scale-95">開啟審核</button>
                </td>
              </tr>
            </tbody></table></div>
        </div>

        <div v-if="!loading && pendingTasks.length === 0" class="mt-8 py-40 bg-white/5 rounded-[4rem] border border-dashed border-white/10 flex flex-col items-center justify-center text-center opacity-40">
           <div class="text-6xl mb-8">✅</div>
           <h3 class="text-2xl font-black uppercase tracking-widest">目前沒有待處理的審核項</h3>
        </div>
      </main>

      <!-- Detailed Multi-User Review Modal -->
      <Transition name="modal">
        <div v-if="showReviewModal && selectedTask" class="fixed inset-0 z-[100] flex items-start justify-center p-4 pt-10 sm:pt-24 overflow-hidden text-white">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showReviewModal = false; selectedTask = null"></div>
          <div class="relative z-10 bg-gray-900 border border-white/10 w-full max-w-2xl rounded-[3rem] p-12 shadow-2xl max-h-[85vh] overflow-y-auto custom-scrollbar">
            <h2 class="text-3xl font-black text-white mb-2 tracking-tight">成果深度審核</h2>
            <p class="text-white/30 text-sm mb-10 italic">任務: {{ selectedTask.title }}</p>

            <div class="space-y-10">
              <!-- Assignee Loop -->
              <div v-for="user in selectedTask.assignees.filter((a:any)=>a.pivot_status === 'pending_review')" :key="user.id" 
                   class="bg-white/5 border border-white/10 rounded-[2.5rem] p-8 hover:border-blue-500/30 transition-all">
                
                <div class="flex items-center justify-between mb-6">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center font-black text-lg border-2 border-white/20 shadow-lg">{{ user.name.substring(0,1).toUpperCase() }}</div>
                    <div>
                      <p class="text-lg font-black text-white">{{ user.name }}</p>
                      <p class="text-[9px] font-bold text-blue-400 uppercase tracking-widest">接案成員</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-[10px] font-black text-yellow-500/60 uppercase mb-1">待領取獎勵</p>
                    <p class="text-xl font-black text-yellow-400">✦ {{ selectedTask.reward_points }}</p>
                  </div>
                </div>

                <!-- Proof Image -->
                <div v-if="user.pivot_evidence_image_url" class="mb-6 rounded-3xl overflow-hidden border border-white/10 shadow-2xl">
                  <img :src="user.pivot_evidence_image_url" class="w-full h-64 object-cover">
                </div>

                <!-- Proof Text -->
                <div class="bg-black/40 rounded-2xl p-5 mb-8">
                  <p class="text-[10px] font-black text-white/30 uppercase mb-2 tracking-widest">提交說明</p>
                  <p class="text-white/80 text-sm leading-relaxed italic italic">"{{ user.pivot_evidence_text || '未提供詳細文字說明' }}"</p>
                </div>

                <!-- Individual Actions -->
                <div class="flex space-x-3">
                   <button @click="handleReject(user.id)" class="flex-1 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 font-black py-4 rounded-2xl transition-all active:scale-95 text-xs uppercase tracking-widest">✘ 退回修正</button>
                   <button @click="handleApprove(user.id)" class="flex-2 bg-green-600 hover:bg-green-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-green-600/30 active:scale-95 text-xs uppercase tracking-widest">✔ 審核通過並給點</button>
                </div>
              </div>
            </div>

            <button @click="showReviewModal = false" class="w-full mt-10 py-4 text-white/20 font-bold hover:text-white transition-all uppercase text-[10px] tracking-[0.3em] border-t border-white/5">稍後再處理其他成員</button>
          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.9) translateY(40px); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>
