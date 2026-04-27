<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import Navbar from '../components/Navbar.vue'

const auth = useAuthStore()
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
      axios.get('/api/tasks?all=true', config),
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

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed text-white font-sans overflow-x-hidden">
    <div class="min-h-screen backdrop-blur-sm bg-black/10 overflow-x-hidden">
      
      <Navbar />

      <main class="p-4 md:p-8 max-w-[1600px] mx-auto text-white">
        <header class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 md:mb-12 gap-4">
          <div>
            <h1 class="text-3xl md:text-5xl font-black mb-2 tracking-tighter drop-shadow-2xl">全域任務監控</h1>
            <p class="text-blue-100/60 font-bold uppercase tracking-widest text-[10px] md:text-xs">監測系統所有專案與公海徵集計畫之進度</p>
          </div>
          <button @click="openCreateModal" class="w-full md:w-auto bg-blue-600 hover:bg-blue-500 text-white font-black py-3.5 md:py-4 px-8 md:px-10 rounded-2xl shadow-xl shadow-blue-600/30 transition-all transform active:scale-95 text-xs uppercase tracking-widest">+ 建立新任務</button>
        </header>

        <div v-if="loading" class="flex justify-center py-40">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-white/20 border-t-blue-500"></div>
        </div>
        
        <div v-else>
          <!-- Desktop View -->
          <div class="hidden lg:block bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-white/5 bg-white/5 flex justify-between items-center">
              <h3 class="font-black text-white text-lg tracking-tighter uppercase ml-2">所有任務清單</h3>
              <span class="bg-white/10 text-white text-xs font-bold px-4 py-1 rounded-full border border-white/5">{{ tasks.length }} 項工作</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="text-[10px] font-black uppercase tracking-[0.25em] text-white/30 border-b border-white/5">
                    <th class="px-10 py-5">任務標題</th>
                    <th class="px-10 py-5">所屬專案 / 部門</th>
                    <th class="px-10 py-5 text-center">狀態</th>
                    <th class="px-10 py-5">獎勵點數</th>
                    <th class="px-10 py-5 text-right">管理操作</th>
                  </tr>
                </thead>
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
                </tbody>
              </table>
            </div>
          </div>

          <!-- Mobile View -->
          <div class="lg:hidden space-y-4">
            <div v-for="task in tasks" :key="task.id" class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2rem] p-5 shadow-xl relative overflow-hidden group">
              <div class="flex justify-between items-start mb-4">
                <div class="min-w-0">
                  <div class="flex items-center space-x-2 mb-1">
                    <span v-if="task.is_crowdsourced" class="text-xs">🔥</span>
                    <h4 class="text-white font-black text-lg truncate">{{ task.title }}</h4>
                  </div>
                  <p class="text-white/40 text-[10px] uppercase font-bold tracking-widest">{{ task.project?.name || '無專案' }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-tighter border border-white/10" :class="{
                  'bg-gray-500/20 text-gray-400': task.status === 'Open',
                  'bg-yellow-500/20 text-yellow-400': task.status === 'In Progress',
                  'bg-blue-500/20 text-blue-400': task.status === 'Review',
                  'bg-green-500/20 text-green-400': task.status === 'Done'
                }">{{ task.status }}</span>
              </div>

              <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                  <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-1">獎勵</p>
                  <div class="text-yellow-400 font-black flex items-center space-x-1">
                    <span class="text-xs">✦</span><span class="text-lg leading-none">{{ task.reward_points }}</span>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-1">部門</p>
                  <span class="text-[10px] font-black text-blue-300 uppercase truncate block">{{ task.department }}</span>
                </div>
              </div>

              <div class="flex items-center justify-end pt-4 border-t border-white/5 gap-2">
                <button @click="openEditModal(task)" class="flex-1 bg-white/10 hover:bg-white/20 text-white font-black text-[10px] py-2.5 rounded-xl transition-all border border-white/10 uppercase tracking-widest">任務詳情</button>
                <button @click="deleteTask(task.id)" class="p-2.5 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2.5"></path></svg></button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- Edit Task Modal -->
      <Transition name="modal">
        <div v-if="showEditModal" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showEditModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-2xl rounded-t-[3rem] sm:rounded-[3rem] p-8 md:p-12 shadow-2xl max-h-[95vh] overflow-y-auto custom-scrollbar text-white">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-2 tracking-tight">{{ isEditing ? '任務資產維護' : '啟動新工作項' }}</h2>
            <p class="text-white/30 text-[10px] md:text-sm mb-8 md:mb-10 italic">精確調整任務分配、獎勵點數與公海徵集設定</p>
            
            <div class="space-y-6 md:space-y-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">
                <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">任務名稱</label><input v-model="taskForm.title" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base"></div>
                <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">關聯專案</label>
                  <div class="relative">
                    <div @click="dropdowns.taskProject = !dropdowns.taskProject" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all"><span :class="taskForm.project_id ? 'text-white font-bold' : 'text-white/20'" class="text-sm">{{ projects.find(p=>p.id === taskForm.project_id)?.name || '選擇關聯專案' }}</span><svg class="w-4 h-4 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg></div>
                    <div v-if="dropdowns.taskProject" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-40 overflow-y-auto custom-scrollbar">
                      <div v-for="p in projects" :key="p.id" @click="selectProject(p)" class="px-5 py-3 text-white font-bold hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0 text-sm">{{ p.name }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-8">
                <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">獎勵點數 (PTS)</label><input v-model="taskForm.reward_points" type="number" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-yellow-400 font-black outline-none focus:border-yellow-500 transition-all text-sm md:text-base"></div>
                <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">截止日期</label><input v-model="taskForm.due_date" type="date" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base"></div>
                <div>
                  <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">當前狀態</label>
                  <div class="relative">
                    <div @click="dropdowns.taskStatus = !dropdowns.taskStatus" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                      <span class="font-black text-[10px] md:text-xs uppercase tracking-widest">{{ taskForm.status }}</span>
                      <svg class="w-4 h-4 text-white/20 transition-transform" :class="{'rotate-180': dropdowns.taskStatus}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg>
                    </div>
                    <div v-if="dropdowns.taskStatus" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl">
                      <div v-for="st in ['Open', 'In Progress', 'Review', 'Done']" :key="st" @click="taskForm.status = st; dropdowns.taskStatus = false" class="px-5 py-3 text-white font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0 flex items-center justify-between group">
                        {{ st }}
                        <div v-if="taskForm.status === st" class="w-2 h-2 bg-blue-400 rounded-full shadow-[0_0_8px_rgba(96,165,250,0.8)]"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-orange-500/5 border border-orange-500/20 p-5 md:p-8 rounded-[2rem] md:rounded-[2.5rem]">
                <div class="flex items-center justify-between mb-4 md:mb-6">
                  <div class="flex items-center space-x-3">
                    <span class="text-xl md:text-2xl">🔥</span>
                    <h4 class="font-black text-orange-400 text-[10px] md:text-sm tracking-widest uppercase">公海徵集設定</h4>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="taskForm.is_crowdsourced" class="sr-only peer">
                    <div class="w-12 h-6 md:w-14 md:h-7 bg-white/10 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:rounded-full after:h-4 md:after:h-5 after:w-5 md:after:w-6 after:transition-all peer-checked:bg-orange-600"></div>
                  </label>
                </div>
                <div v-if="taskForm.is_crowdsourced" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                  <div>
                    <label class="block text-[9px] font-black text-white/40 uppercase mb-2 ml-1">名額上限</label>
                    <input v-model="taskForm.max_assignees" type="number" class="w-full bg-black/40 border border-orange-500/20 rounded-xl px-5 py-3 text-orange-400 font-black outline-none focus:border-orange-500 transition-all text-sm">
                  </div>
                  <p class="text-[9px] md:text-[11px] text-white/30 flex items-center italic">開啟後全體成員皆可接取此任務。</p>
                </div>
              </div>

              <div>
                <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">詳細描述</label>
                <textarea v-model="taskForm.details" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white font-medium outline-none focus:border-blue-500 transition-all h-24 resize-none text-sm"></textarea>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-10 md:mt-12 pt-6 md:pt-8 border-t border-white/5">
              <button @click="showEditModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs">取消</button>
              <button @click="saveTask" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs md:text-sm">{{ isEditing ? '更新任務' : '建立任務' }}</button>
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

@media (max-width: 640px) {
  .modal-enter-from, .modal-leave-to { transform: translateY(100%); opacity: 0; }
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
