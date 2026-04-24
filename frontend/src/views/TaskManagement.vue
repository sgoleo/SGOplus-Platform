<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

const auth = useAuthStore()
const router = useRouter()
const tasks = ref<any[]>([])
const projects = ref<any[]>([])
const departments = ref<any[]>([])
const loading = ref(true)

// Modals State
const showEditModal = ref(false)
const selectedTask = ref<any>(null)
const isEditing = ref(false)
const dropdowns = ref({
  taskProject: false,
  taskStatus: false
})

// Form State
const taskForm = ref({
  title: '',
  details: '',
  project_id: null as number | null,
  status: 'Open',
  due_date: '',
  reward_points: 0,
  is_crowdsourced: false,
  max_assignees: 1,
  department: ''
})

const selectProject = (p: any) => {
  taskForm.value.project_id = p.id
  taskForm.value.department = p.department
  dropdowns.value.taskProject = false
}

const fetchData = async () => {
  loading.value = true
  const config = { headers: { Authorization: `Bearer ${auth.token}` } }
  try {
    const [taskRes, projRes, deptRes] = await Promise.all([
      axios.get('/api/tasks?all=true', config), // 加入 all=true 獲取全域清單
      axios.get('/api/projects', config),
      axios.get('/api/departments', config)
    ])
    
    tasks.value = Array.isArray(taskRes.data) ? taskRes.data : (taskRes.data.data || taskRes.data.value || [])
    projects.value = Array.isArray(projRes.data) ? projRes.data : (projRes.data.value || projRes.data.data || [])
    departments.value = Array.isArray(deptRes.data) ? deptRes.data : (deptRes.data.value || deptRes.data.data || [])
  } catch (err) {
    console.error('Failed to fetch data', err)
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEditing.value = false
  selectedTask.value = null
  taskForm.value = { 
    title: '', details: '', project_id: null, status: 'Open', due_date: '', 
    reward_points: 0, is_crowdsourced: false, max_assignees: 1, department: '' 
  }
  showEditModal.value = true
}

const openEditModal = (task: any) => {
  isEditing.value = true
  selectedTask.value = task
  taskForm.value = {
    title: task.title,
    details: task.details,
    project_id: task.project?.id,
    status: task.status,
    due_date: task.due_date ? task.due_date.split(' ')[0] : '',
    reward_points: task.reward_points,
    is_crowdsourced: task.is_crowdsourced,
    max_assignees: task.max_assignees,
    department: task.department
  }
  showEditModal.value = true
}

const saveTask = async () => {
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    if (isEditing.value && selectedTask.value) {
      await axios.put(`/api/tasks/${selectedTask.value.id}`, taskForm.value, config)
    } else {
      await axios.post('/api/tasks', taskForm.value, config)
    }
    showEditModal.value = false
    fetchData()
  } catch (err) { alert('儲存失敗') }
}

const deleteTask = async (id: number) => {
  if (!confirm('確定要永久刪除此任務嗎？')) return
  try {
    await axios.delete(`/api/tasks/${id}`, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
  } catch (err) { alert('刪除失敗') }
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
            <router-link to="/" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all text-white/40 hover:text-white hover:bg-white/5">儀表板</router-link>
            <router-link v-if="auth.roles.includes('SuperAdmin')" to="/projects" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all text-white/40 hover:text-white hover:bg-white/5">專案管理</router-link>
            <router-link v-if="auth.roles.includes('SuperAdmin')" to="/tasks-management" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all bg-blue-600 text-white shadow-lg shadow-blue-600/20">任務管理</router-link>
            <router-link v-if="auth.roles.includes('SuperAdmin')" to="/users" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all text-white/40 hover:text-white hover:bg-white/5">用戶管理</router-link>
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
          <button @click="handleLogout" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-full p-2.5 transition shadow-lg active:scale-90">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-width="2.5"></path></svg>
          </button>
        </div>
      </nav>

      <main class="p-8 max-w-[1600px] mx-auto text-white">
        <header class="flex justify-between items-end mb-12">
          <div>
            <h1 class="text-5xl font-black mb-3 tracking-tighter drop-shadow-2xl">全域任務監控</h1>
            <p class="text-blue-100/60 font-bold uppercase tracking-[0.3em] text-xs ml-1">監測系統所有開發、研究與公海徵集計畫之進度</p>
          </div>
          <button @click="openCreateModal" class="bg-blue-600 hover:bg-blue-500 text-white font-black py-4 px-10 rounded-2xl shadow-xl shadow-blue-600/30 transition-all transform hover:-translate-y-1 active:scale-95 text-xs uppercase tracking-widest">+ 建立新任務</button>
        </header>

        <div v-if="loading" class="flex justify-center py-40"><div class="animate-spin rounded-full h-16 w-16 border-4 border-white/20 border-t-blue-500"></div></div>
        
        <!-- Task Grid Table -->
        <div v-else class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-black/40">
          <div class="p-6 border-b border-white/5 bg-white/5 flex justify-between items-center">
            <h3 class="font-black text-white text-lg tracking-tighter uppercase ml-2">所有任務清單</h3>
            <span class="bg-white/10 text-white text-xs font-bold px-4 py-1 rounded-full border border-white/5">{{ tasks.length }} 項工作</span>
          </div>

          <div class="overflow-x-auto"><table class="w-full text-left border-collapse"><thead><tr class="text-[10px] font-black uppercase tracking-[0.25em] text-white/30 border-b border-white/5"><th class="px-10 py-5">任務標題</th><th class="px-10 py-5">所屬專案 / 部門</th><th class="px-10 py-5 text-center">狀態</th><th class="px-10 py-5">獎勵點數</th><th class="px-10 py-5 text-right">管理操作</th></tr></thead>
            <tbody class="divide-y divide-white/5 text-white">
              <tr v-for="task in tasks" :key="task.id" class="hover:bg-white/5 transition-all group">
                <td class="px-10 py-8">
                  <div class="flex items-center space-x-4">
                    <span v-if="task.is_crowdsourced" class="text-xs">🔥</span>
                    <p class="font-black text-white text-lg group-hover:text-blue-300 transition-colors">{{ task.title }}</p>
                  </div>
                </td>
                <td class="px-10 py-8">
                  <div class="flex flex-col">
                    <span class="text-xs font-bold text-white/70">{{ task.project?.name || '無專案' }}</span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-blue-300 mt-1 opacity-50">{{ task.department }}</span>
                  </div>
                </td>
                <td class="px-10 py-8 text-center">
                   <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border border-white/10" :class="{
                     'bg-gray-500/20 text-gray-400': task.status === 'Open',
                     'bg-yellow-500/20 text-yellow-400': task.status === 'In Progress',
                     'bg-blue-500/20 text-blue-400': task.status === 'Review',
                     'bg-green-500/20 text-green-400': task.status === 'Done'
                   }">{{ task.status }}</span>
                </td>
                <td class="px-10 py-8">
                  <div class="flex items-center space-x-2 text-yellow-400 font-black">
                    <span class="text-xs">✦</span><span>{{ task.reward_points }}</span>
                  </div>
                </td>
                <td class="px-10 py-8 text-right">
                  <div class="flex justify-end items-center space-x-3">
                    <button @click="openEditModal(task)" class="bg-white/10 hover:bg-white/20 text-white font-black text-[10px] px-6 py-2.5 rounded-xl transition-all border border-white/10">管理</button>
                    <button @click="deleteTask(task.id)" class="p-2.5 bg-red-500/10 hover:bg-red-500 text-red-500 rounded-xl transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2.5"></path></svg></button>
                  </div>
                </td>
              </tr>
            </tbody></table></div>
        </div>
      </main>

      <!-- Edit Task Modal -->
      <Transition name="modal">
        <div v-if="showEditModal" class="fixed inset-0 z-[100] flex items-start justify-center p-4 pt-10 sm:pt-24 overflow-hidden">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showEditModal = false"></div>
          <div class="relative z-10 bg-gray-900 border border-white/10 w-full max-w-2xl rounded-[3rem] p-12 shadow-2xl max-h-[85vh] overflow-y-auto custom-scrollbar text-white">
            <h2 class="text-3xl font-black text-white mb-2 tracking-tight">{{ isEditing ? '任務資產維護' : '啟動新工作項' }}</h2>
            <p class="text-white/30 text-sm mb-10 italic">精確調整任務分配、獎勵點數與公海徵集設定</p>
            <div class="space-y-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div><label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">任務名稱</label><input v-model="taskForm.title" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all"></div>
                <div><label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">關聯專案</label><div class="relative">
                  <div @click="dropdowns.taskProject = !dropdowns.taskProject" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all text-white"><span :class="taskForm.project_id ? 'text-white font-bold' : 'text-white/20'">{{ projects.find(p=>p.id === taskForm.project_id)?.name || '選擇關聯專案' }}</span><svg class="w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg></div>
                  <div v-if="dropdowns.taskProject" class="absolute z-[110] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-60 overflow-y-auto custom-scrollbar">
                    <div v-for="p in projects" :key="p.id" @click="selectProject(p)" class="px-6 py-4 text-white font-bold hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0">{{ p.name }}</div>
                  </div></div></div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div><label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">獎勵點數 (PTS)</label><input v-model="taskForm.reward_points" type="number" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-yellow-400 font-black outline-none focus:border-yellow-500 transition-all"></div>
                <div><label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">截止日期</label><input v-model="taskForm.due_date" type="date" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all"></div>
                
                <!-- Custom Status Dropdown -->
                <div>
                  <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">當前狀態</label>
                  <div class="relative">
                    <div @click="dropdowns.taskStatus = !dropdowns.taskStatus" 
                         class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                      <span class="font-black text-xs uppercase tracking-widest">{{ taskForm.status }}</span>
                      <svg class="w-5 h-5 text-white/20 transition-transform" :class="{'rotate-180': dropdowns.taskStatus}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg>
                    </div>
                    <div v-if="dropdowns.taskStatus" class="absolute z-[110] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl">
                      <div v-for="st in ['Open', 'In Progress', 'Review', 'Done']" :key="st" 
                           @click="taskForm.status = st; dropdowns.taskStatus = false" 
                           class="px-6 py-4 text-white font-black text-xs uppercase tracking-widest hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0 flex items-center justify-between group">
                        {{ st }}
                        <div v-if="taskForm.status === st" class="w-2 h-2 bg-blue-400 rounded-full shadow-[0_0_8px_rgba(96,165,250,0.8)]"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bg-orange-500/5 border border-orange-500/20 p-8 rounded-[2.5rem]"><div class="flex items-center justify-between mb-6"><div class="flex items-center space-x-3"><span class="text-2xl">🔥</span><h4 class="font-black text-orange-400 text-sm tracking-widest uppercase">公海徵集計畫設定</h4></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" v-model="taskForm.is_crowdsourced" class="sr-only peer"><div class="w-14 h-7 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:rounded-full after:h-5 after:w-6 after:transition-all peer-checked:bg-orange-600"></div></label></div><div v-if="taskForm.is_crowdsourced" class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-[10px] font-black text-white/40 uppercase mb-3 ml-1">名額上限 (人數)</label><input v-model="taskForm.max_assignees" type="number" class="w-full bg-black/40 border border-orange-500/20 rounded-2xl px-6 py-4 text-orange-400 font-black outline-none focus:border-orange-500 transition-all"></div><p class="text-[11px] text-white/30 flex items-center italic">開啟公海徵集後，全事業組成員皆可競爭接取此任務。</p></div></div>
              <div><label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1 text-white">詳細描述</label><textarea v-model="taskForm.details" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-medium outline-none focus:border-blue-500 transition-all h-24 resize-none"></textarea></div>
            </div>
            <div class="flex space-x-4 mt-12 pt-8 border-t border-white/5">
              <button @click="showEditModal = false" class="flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs">取消</button>
              <button @click="saveTask" class="flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-sm">{{ isEditing ? '更新任務' : '建立任務' }}</button>
            </div>
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
