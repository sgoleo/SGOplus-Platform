<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Navbar from '../components/Navbar.vue'
import TaskDetailModal from '../components/TaskDetailModal.vue'

const auth = useAuthStore()
const router = useRouter()
const poolTasks = ref<any[]>([])
const projects = ref<any[]>([])
const departments = ref<any[]>([])
const loading = ref(true)

// Modal State
const showPublishModal = ref(false)
const showProjectDropdown = ref(false)
const showDeptDropdown = ref(false)
const showDetail = ref(false)
const detailTask = ref<any>(null)
const newTask = ref({
  title: '',
  details: '',
  project_id: null as number | null,
  department: 'Public', // 預設為全域開放
  reward_points: 100,
  max_assignees: 5,
  due_date: '',
  is_crowdsourced: true,
  status: 'Open'
})

const fetchPool = async () => {
  loading.value = true
  const config = { headers: { Authorization: `Bearer ${auth.token}` } }
  
  // 1. Fetch Projects
  try {
    const res = await axios.get('/api/projects', config)
    const rawData = res.data
    projects.value = Array.isArray(rawData) ? rawData : (rawData.value || rawData.data || [])
  } catch (err) { console.error('Failed projects', err) }

  // 2. Fetch Departments
  try {
    const res = await axios.get('/api/departments', config)
    const rawData = res.data
    const rawDepts = Array.isArray(rawData) ? rawData : (rawData.value || rawData.data || [])
    departments.value = [{ id: 'public', name: 'Public' }, ...rawDepts]
  } catch (err) { console.error('Failed depts', err) }

  // 3. Fetch Tasks Pool
  try {
    const res = await axios.get('/api/tasks-pool', config)
    const rawData = res.data
    poolTasks.value = Array.isArray(rawData) ? rawData : (rawData.value || rawData.data || [])
  } catch (err) { console.error('Failed pool', err) }

  // 4. Fetch User Info
  try {
    const res = await axios.get('/api/user', config)
    const userData = res.data.value || res.data.data || res.data
    auth.user.points = userData.points || 0
  } catch (err) { console.error('Failed user', err) }

  loading.value = false
}

const claimTask = async (taskId: number) => {
  try {
    const res = await axios.post(`/api/tasks/${taskId}/claim`, {}, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    alert(res.data.message || '接取成功！')
    router.push('/')
  } catch (err: any) {
    alert(err.response?.data?.message || '接取失敗')
    fetchPool()
  }
}

const publishTask = async () => {
  if (!newTask.value.project_id) return alert('請選擇關聯專案')
  try {
    await axios.post('/api/tasks', {
      ...newTask.value,
      is_crowdsourced: true,
      max_assignees: parseInt(newTask.value.max_assignees.toString())
    }, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    showPublishModal.value = false
    fetchPool()
  } catch (err) {
    alert('發布失敗')
  }
}

const selectProject = (p: any) => {
  newTask.value.project_id = p.id
  showProjectDropdown.value = false
}

const selectDept = (name: string) => {
  newTask.value.department = name
  showDeptDropdown.value = false
}

onMounted(fetchPool)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1635776062127-d379bfcba9f8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed overflow-x-hidden">
    <div class="min-h-screen backdrop-blur-md bg-black/40 font-sans text-white overflow-x-hidden">
      
      <Navbar />

      <main class="p-4 md:p-8 max-w-[1600px] mx-auto text-white">
        <header class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 md:mb-16 gap-4">
          <div>
            <h1 class="text-3xl md:text-5xl font-black mb-2 drop-shadow-lg tracking-tight">任務公海</h1>
            <p class="text-blue-100/50 font-bold uppercase tracking-widest text-[10px] md:text-xs">接取群眾外包任務，累積個人成就與核心點數</p>
          </div>
          
          <button v-if="auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')"
                  @click="showPublishModal = true"
                  class="w-full md:w-auto bg-blue-600 hover:bg-blue-500 text-white font-black py-4 px-8 rounded-2xl md:rounded-3xl shadow-xl transition-all transform active:scale-95 text-xs uppercase tracking-widest border border-blue-400/30">+ 發布公海任務</button>
        </header>

        <div v-if="loading" class="flex justify-center py-40">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-white/20 border-t-orange-500 shadow-2xl"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 md:gap-10">
          <div v-for="task in poolTasks" :key="task.id" 
               @click="detailTask = task; showDetail = true"
               class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-[2.5rem] md:rounded-[3rem] p-6 md:p-10 hover:bg-white/10 transition-all group relative overflow-hidden shadow-2xl cursor-pointer">
            <div class="absolute -top-32 -right-32 w-64 h-64 bg-orange-500/10 blur-[100px] group-hover:bg-orange-500/20 transition-all duration-700"></div>
            
            <div class="relative z-10 h-full flex flex-col">
              <div class="flex justify-between items-start mb-6 md:mb-8">
                <span class="text-[9px] md:text-[10px] font-black uppercase tracking-widest text-blue-300 bg-blue-500/10 px-3 py-1 rounded-full border border-blue-500/20" :class="{'text-purple-300 bg-purple-500/10 border-purple-500/20': task.department === 'Public'}">
                  {{ task.department === 'Public' ? 'Global / Public' : (task.project?.name || '公海項目') }}
                </span>
                <div class="flex items-center space-x-1.5 text-yellow-400 font-black">
                  <span class="text-sm md:text-base drop-shadow-md">✦</span>
                  <span class="text-2xl md:text-3xl drop-shadow-[0_0_15px_rgba(234,179,8,0.4)]">{{ task.reward_points }}</span>
                </div>
              </div>

              <h3 class="text-2xl md:text-3xl font-black text-white mb-3 md:mb-4 group-hover:text-orange-300 transition-colors leading-tight tracking-tight">{{ task.title }}</h3>
              <p class="text-white/30 text-xs md:text-sm mb-8 md:mb-10 line-clamp-3 leading-relaxed font-medium flex-1">{{ task.details || '無詳細說明' }}</p>

              <div class="flex items-center justify-between border-t border-white/5 pt-6 md:pt-8">
                <div>
                  <p class="text-[8px] md:text-[9px] font-black text-white/20 uppercase mb-1 md:mb-2 tracking-widest">接取進度</p>
                  <span class="text-xs md:text-sm font-black text-white/80">{{ task.assignees_count }} <span class="text-white/30 text-[9px] md:text-[10px] font-bold mx-1">/</span> {{ task.max_assignees }}</span>
                </div>
                <button @click.stop="claimTask(task.id)" class="bg-orange-600 hover:bg-orange-500 text-white font-black px-6 md:px-8 py-3.5 md:py-4 rounded-2xl md:rounded-[1.8rem] transition-all shadow-lg active:scale-90 border border-orange-400/30 text-[10px] md:text-[11px] uppercase tracking-widest">立即接單</button>
              </div>
            </div>
          </div>

          <div v-if="poolTasks.length === 0" class="col-span-full py-40 bg-white/5 rounded-[3rem] md:rounded-[4rem] border border-dashed border-white/10 flex flex-col items-center justify-center text-center px-10 opacity-50">
             <div class="text-5xl md:text-6xl mb-6 md:mb-8 text-white">🌊</div>
             <h3 class="text-xl md:text-2xl font-black uppercase tracking-widest text-white">公海一片風平浪靜</h3>
          </div>
        </div>
      </main>

      <!-- Publish Modal -->
      <Transition name="modal">
        <div v-if="showPublishModal" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showPublishModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-2xl rounded-t-[3rem] sm:rounded-[3.5rem] p-8 md:p-12 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar text-white">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-4xl font-black text-white mb-1 tracking-tighter">發布公海任務</h2>
            <p class="text-blue-400 text-[10px] font-black uppercase tracking-widest mb-8 md:mb-10 italic">Crowdsourcing Campaign</p>

            <div class="space-y-6 md:space-y-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">
                <div>
                  <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">任務標題</label>
                  <input v-model="newTask.title" type="text" placeholder="輸入標題..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base">
                </div>
                <div>
                  <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">發布範圍 / 事業組</label>
                  <div class="relative">
                    <div @click="showDeptDropdown = !showDeptDropdown" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                      <span :class="newTask.department ? 'text-white font-bold' : 'text-white/20'" class="text-sm">
                        {{ newTask.department || '選擇範圍' }}
                      </span>
                      <svg class="w-5 h-5 text-white/20 transition-transform" :class="{'rotate-180': showDeptDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg>
                    </div>
                    <div v-if="showDeptDropdown" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-60 overflow-y-auto custom-scrollbar">
                      <div v-for="dept in departments" :key="dept.id" @click="selectDept(dept.name)" class="px-6 py-3 text-white font-black hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0 flex items-center justify-between group">
                        <span :class="{'text-purple-400': dept.name === 'Public'}" class="text-sm">{{ dept.name }}</span>
                        <div v-if="newTask.department === dept.name" class="w-2 h-2 bg-blue-400 rounded-full"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">
                 <div>
                  <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">關聯專案</label>
                  <div class="relative">
                    <div @click="showProjectDropdown = !showProjectDropdown" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                      <span :class="newTask.project_id ? 'text-white font-bold' : 'text-white/20'" class="text-sm">
                        {{ projects.find(p => p.id === newTask.project_id)?.name || '選擇專案' }}
                      </span>
                      <svg class="w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg>
                    </div>
                    <div v-if="showProjectDropdown" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-60 overflow-y-auto custom-scrollbar">
                      <div v-for="p in projects" :key="p.id" @click="selectProject(p)" class="px-6 py-3 text-white font-black hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0 text-sm">{{ p.name }}</div>
                    </div>
                  </div>
                </div>
                <div>
                   <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">截止日期</label>
                   <input v-model="newTask.due_date" type="date" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base">
                </div>
              </div>

              <div>
                <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">任務詳情</label>
                <textarea v-model="newTask.details" placeholder="詳細描述工作內容..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white font-medium outline-none focus:border-blue-500 transition-all h-28 resize-none text-sm"></textarea>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">
                <div>
                  <label class="block text-[9px] font-black text-yellow-500/80 uppercase mb-2 tracking-widest ml-1">獎勵 (PTS)</label>
                  <input v-model="newTask.reward_points" type="number" class="w-full bg-black/40 border border-yellow-500/20 rounded-2xl px-5 py-4 text-yellow-400 font-black outline-none focus:border-yellow-500 transition-all text-sm md:text-base">
                </div>
                <div>
                  <label class="block text-[9px] font-black text-orange-500/80 uppercase mb-2 tracking-widest ml-1">接取名額</label>
                  <input v-model="newTask.max_assignees" type="number" class="w-full bg-black/40 border border-orange-500/20 rounded-2xl px-5 py-4 text-orange-400 font-black outline-none focus:border-orange-500 transition-all text-sm md:text-base">
                </div>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-10 md:mt-12 pt-6 md:pt-10 border-t border-white/5">
              <button @click="showPublishModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs tracking-widest">取消</button>
              <button @click="publishTask" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs md:text-sm">發布公海計畫</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Task Detail Modal -->
      <TaskDetailModal :show="showDetail" :task="detailTask" @close="showDetail = false">
        <template #actions>
          <button @click="claimTask(detailTask.id); showDetail = false" 
                  class="flex-1 bg-orange-600 hover:bg-orange-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs">
            立即接單
          </button>
        </template>
      </TaskDetailModal>

    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }

.line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

.modal-enter-active, .modal-leave-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.9) translateY(40px); }

@media (max-width: 640px) {
  .modal-enter-from, .modal-leave-to { transform: translateY(100%); opacity: 0; }
}
</style>
