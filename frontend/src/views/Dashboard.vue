<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'
import draggable from 'vuedraggable'

const auth = useAuthStore()
const router = useRouter()
const tasks = ref<any[]>([])
const projects = ref<any[]>([])
const departments = ref<any[]>([])
const loading = ref(true)

// Modals State
const showProjectModal = ref(false)
const showTaskModal = ref(false)
const showReviewModal = ref(false)
const showDeptModal = ref(false)
const selectedTask = ref<any>(null)
const selectedDept = ref<any>(null)

const newTask = ref({ title: '', details: '', project_id: '', status: 'Open', due_date: '', reward_points: 0 })
const newProject = ref({ name: '', department: '', description: '' })
const newDept = ref({ name: '', code: '', description: '' })

// Custom Dropdown Logic
const dropdowns = ref({
  projectDept: false,
  taskProject: false
})

const selectDepartment = (dept: string) => {
  newProject.value.department = dept
  dropdowns.value.projectDept = false
}

const selectTaskProject = (project: any) => {
  newTask.value.project_id = project.id
  dropdowns.value.taskProject = false
}

// Kanban Columns
const columns = computed(() => {
  return [
    { id: 'Open', title: '待辦事項', tasks: tasks.value.filter(t => t.status === 'Open') },
    { id: 'In Progress', title: '進行中', tasks: tasks.value.filter(t => t.status === 'In Progress') },
    { id: 'Review', title: '待審核', tasks: tasks.value.filter(t => t.status === 'Review') },
    { id: 'Done', title: '已完成', tasks: tasks.value.filter(t => t.status === 'Done') }
  ]
})

const fetchData = async () => {
  try {
    const [taskRes, projectRes, userRes, deptRes] = await Promise.all([
      axios.get('/api/tasks', { headers: { Authorization: `Bearer ${auth.token}` } }),
      axios.get('/api/projects', { headers: { Authorization: `Bearer ${auth.token}` } }),
      axios.get('/api/user', { headers: { Authorization: `Bearer ${auth.token}` } }),
      axios.get('/api/departments', { headers: { Authorization: `Bearer ${auth.token}` } })
    ])
    tasks.value = taskRes.data.data
    projects.value = projectRes.data
    departments.value = deptRes.data
    // Update points in store
    auth.user.points = userRes.data.points
  } catch (err) {
    console.error('Failed to fetch data', err)
  } finally {
    loading.value = false
  }
}

const openDeptModal = (dept: any = null) => {
  if (dept) {
    selectedDept.value = dept
    newDept.value = { ...dept }
  } else {
    selectedDept.value = null
    newDept.value = { name: '', code: '', description: '' }
  }
  showDeptModal.value = true
}

const saveDept = async () => {
  try {
    if (selectedDept.value) {
      await axios.put(`/api/departments/${selectedDept.value.id}`, newDept.value, {
        headers: { Authorization: `Bearer ${auth.token}` }
      })
    } else {
      await axios.post('/api/departments', newDept.value, {
        headers: { Authorization: `Bearer ${auth.token}` }
      })
    }
    showDeptModal.value = false
    fetchData()
  } catch (err) { alert('儲存部門失敗') }
}

const deleteDept = async (id: number) => {
  if (!confirm('確定要刪除此部門嗎？')) return
  try {
    await axios.delete(`/api/departments/${id}`, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
  } catch (err) { alert('刪除失敗') }
}

const openReviewModal = (task: any) => {
  if (task.status === 'Review' && (auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects'))) {
    selectedTask.value = task
    showReviewModal.value = true
  }
}

const handleApprove = async () => {
  if (!selectedTask.value) return;
  try {
    await axios.post(`/api/tasks/${selectedTask.value.id}/approve`, {}, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    showReviewModal.value = false
    selectedTask.value = null
    fetchData()
  } catch (err) { alert('審核失敗') }
}

const handleReject = async () => {
  if (!selectedTask.value) return;
  try {
    await axios.post(`/api/tasks/${selectedTask.value.id}/reject`, {}, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    showReviewModal.value = false
    selectedTask.value = null
    fetchData()
  } catch (err) { alert('退回失敗') }
}

const handleStatusChange = async (event: any, newStatus: string) => {
  if (event.added) {
    const task = event.added.element
    try {
      await axios.put(`/api/tasks/${task.id}`, { status: newStatus }, {
        headers: { Authorization: `Bearer ${auth.token}` }
      })
      // Update local state status
      const index = tasks.value.findIndex(t => t.id === task.id)
      if (index !== -1) tasks.value[index].status = newStatus
    } catch (err) {
      console.error('Update failed', err)
      fetchData() // Refresh on error
    }
  }
}

const createProject = async () => {
  try {
    await axios.post('/api/projects', newProject.value, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    showProjectModal.value = false
    newProject.value = { name: '', department: '', description: '' }
    fetchData()
  } catch (err) { alert('建立專案失敗') }
}

const createTask = async (status: string) => {
  try {
    const selectedProject = projects.value.find(p => p.id === parseInt(newTask.value.project_id))
    await axios.post('/api/tasks', {
      ...newTask.value,
      status: status,
      department: selectedProject?.department || auth.user.department
    }, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    showTaskModal.value = false
    newTask.value = { title: '', details: '', project_id: '', status: 'Open', due_date: '' }
    fetchData()
  } catch (err) { alert('建立任務失敗') }
}

const handleLogout = () => { auth.logout(); router.push('/login') }

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed">
    <div class="min-h-screen backdrop-blur-sm bg-black/10">
      
      <!-- Header (Glassmorphism) -->
      <nav class="sticky top-0 z-40 bg-white/20 backdrop-blur-md border-b border-white/20 px-8 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center space-x-4">
          <h2 class="text-2xl font-extrabold text-white tracking-tight drop-shadow-md">SGOplus <span class="text-blue-300">OS</span></h2>
          <span v-if="auth.roles.includes('SuperAdmin')" class="bg-purple-500/80 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">
            ADMIN
          </span>
        </div>
        
        <div class="flex items-center space-x-6">
          <!-- Points Badge -->
          <div class="bg-white/10 backdrop-blur-md border border-yellow-500/30 px-4 py-1.5 rounded-2xl flex items-center space-x-2 shadow-[0_0_15px_rgba(234,179,8,0.2)]">
            <span class="text-yellow-400 text-lg">✦</span>
            <span class="text-white font-black text-sm">{{ auth.user?.points || 0 }} <span class="text-[10px] text-yellow-500/70 ml-0.5">PTS</span></span>
          </div>

          <div class="text-right">
            <p class="text-sm font-bold text-white">{{ auth.user?.name }}</p>
            <p class="text-[10px] font-medium text-blue-100/70 uppercase tracking-widest">{{ auth.user?.department || 'System Wide' }}</p>
          </div>
          <button @click="handleLogout" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-full p-2 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
          </button>
        </div>
      </nav>

      <!-- Dashboard Controls -->
      <header class="p-8 pb-0 max-w-[1600px] mx-auto flex justify-between items-end">
        <div>
          <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg">任務看板</h1>
          <p class="text-blue-100/60 font-medium">管理您的專案與團隊進度</p>
        </div>
        <div class="flex space-x-4">
          <!-- Department Management Button (Admin Only) -->
          <button v-if="auth.roles.includes('SuperAdmin')" 
                  @click="openDeptModal()" 
                  class="bg-white/10 hover:bg-white/20 text-white border border-white/10 font-bold py-3 px-6 rounded-2xl transition-all flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            <span>部門管理</span>
          </button>
          
          <button @click="showProjectModal = true" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-2xl shadow-xl shadow-blue-500/20 transition-all transform hover:-translate-y-1 active:scale-95">
            + 建立新專案
          </button>
        </div>
      </header>

      <!-- Kanban Area -->
      <main class="p-8 max-w-[1600px] mx-auto">
        <div v-if="loading" class="flex justify-center py-40">
          <div class="animate-spin rounded-full h-16 w-16 border-4 border-white/20 border-t-blue-400"></div>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
          <div v-for="col in columns" :key="col.id" class="flex flex-col h-full min-h-[600px]">
            <div class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden flex flex-col h-full shadow-2xl shadow-black/20">
              <div class="p-5 border-b border-white/5 flex justify-between items-center">
                <h3 class="font-black text-white text-lg tracking-tight uppercase">{{ col.title }}</h3>
                <span class="bg-white/10 text-white text-xs font-bold px-3 py-1 rounded-full">{{ col.tasks.length }}</span>
              </div>

              <!-- Draggable Area -->
              <draggable 
                class="flex-1 p-4 space-y-4 overflow-y-auto min-h-[100px]"
                :list="col.tasks"
                group="tasks"
                item-key="id"
                @change="(e) => handleStatusChange(e, col.id)"
                :animation="200"
                ghost-class="opacity-0"
              >
                <template #item="{ element }">
                  <div @click="openReviewModal(element)"
                       class="group bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/30 p-5 rounded-2xl transition-all shadow-lg"
                       :class="[col.id === 'Review' && (auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')) ? 'cursor-pointer hover:bg-blue-500/10 hover:border-blue-400/50' : 'cursor-grab active:cursor-grabbing']">
                    <div class="flex justify-between items-start mb-3">
                      <span class="text-[10px] font-black uppercase tracking-widest text-blue-300/80 bg-blue-500/10 px-2 py-0.5 rounded-md">
                        {{ element.project.name }}
                      </span>
                      <div class="flex -space-x-2">
                        <div class="w-6 h-6 rounded-full bg-blue-400 border-2 border-white/10 text-[8px] flex items-center justify-center font-bold text-white uppercase">US</div>
                      </div>
                    </div>
                    <h4 class="font-bold text-white text-base mb-2 group-hover:text-blue-100 transition-colors">{{ element.title }}</h4>
                    <p class="text-white/40 text-xs line-clamp-2 leading-relaxed mb-4">{{ element.details }}</p>
                    
                    <!-- Reward Points Badge -->
                    <div v-if="element.reward_points > 0" class="mb-4 inline-flex items-center space-x-1 bg-yellow-500/10 border border-yellow-500/20 px-2 py-0.5 rounded text-[10px] text-yellow-400 font-bold">
                      <span>✦</span> <span>{{ element.reward_points }} PTS</span>
                    </div>

                    <!-- Pending Review Status for Normal Members -->
                    <div v-if="col.id === 'Review' && !(auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects'))" class="mb-4 inline-flex items-center space-x-1 bg-gray-500/20 border border-gray-500/30 px-2 py-0.5 rounded text-[10px] text-gray-300 font-bold">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                      <span>審核中...</span>
                    </div>

                    <div class="flex items-center text-[10px] font-bold text-white/30 uppercase tracking-widest">
                      <svg class="w-3.5 h-3.5 mr-1.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      Due: {{ element.due_date ? new Date(element.due_date).toLocaleDateString() : 'No Date' }}
                    </div>
                  </div>
                </template>
              </draggable>

              <button @click="showTaskModal = true; newTask.status = col.id" class="m-4 p-4 rounded-2xl bg-white/5 border border-dashed border-white/10 text-white/50 text-sm font-bold hover:bg-white/10 hover:text-white transition-all flex items-center justify-center">
                + 新增任務
              </button>
            </div>
          </div>
        </div>
      </main>

      <!-- Project Modal -->
      <Transition name="modal">
        <div v-if="showProjectModal" class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 sm:pt-24">
          <div class="absolute inset-0 bg-black/70 backdrop-blur-md" @click="showProjectModal = false"></div>
          <div class="relative bg-gray-900 border border-white/10 w-full max-w-md rounded-[2.5rem] p-10 shadow-2xl shadow-blue-500/10 max-h-[85vh] overflow-y-auto overflow-x-hidden">
            <h2 class="text-3xl font-black text-white mb-8 tracking-tight">建立新專案</h2>
            <div class="space-y-6">
              <div>
                <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">專案名稱</label>
                <input v-model="newProject.name" type="text" placeholder="輸入專案名稱..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all">
              </div>
              <div>
                <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">所屬部門</label>
                <div class="relative">
                  <div @click="dropdowns.projectDept = !dropdowns.projectDept" 
                       class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                    <span>{{ newProject.department || '選擇部門' }}</span>
                    <svg class="w-5 h-5 text-white/20 transition-transform" :class="{'rotate-180': dropdowns.projectDept}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                  </div>
                  <!-- Custom Dropdown List (Dynamic) -->
                  <div v-if="dropdowns.projectDept" class="absolute z-10 w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-xl max-h-60 overflow-y-auto">
                    <div v-for="dept in departments" :key="dept.id"
                         @click="selectDepartment(dept.name)"
                         class="px-5 py-4 text-white hover:bg-blue-600 cursor-pointer transition-colors border-b border-white/5 last:border-0">
                      {{ dept.name }}
                    </div>
                    <div v-if="departments.length === 0" class="px-5 py-4 text-white/30 text-sm italic">
                      暫無部門，請先建立
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">專案描述</label>
                <textarea v-model="newProject.description" placeholder="簡述此專案的目標..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all h-32 resize-none"></textarea>
              </div>
            </div>
            <div class="flex space-x-4 mt-10">
              <button @click="showProjectModal = false" class="flex-1 py-4 text-white/30 font-bold hover:text-white transition-all active:scale-95">取消</button>
              <button @click="createProject" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-blue-600/20 active:scale-95">建立專案</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Task Modal -->
      <Transition name="modal">
        <div v-if="showTaskModal" class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 sm:pt-24">
          <div class="absolute inset-0 bg-black/70 backdrop-blur-md" @click="showTaskModal = false"></div>
          <div class="relative bg-gray-900 border border-white/10 w-full max-w-md rounded-[2.5rem] p-10 shadow-2xl shadow-blue-500/10 max-h-[85vh] overflow-y-auto overflow-x-hidden">
            <h2 class="text-3xl font-black text-white mb-2 tracking-tight">新增任務</h2>
            <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-8">狀態: {{ newTask.status }}</p>
            
            <div class="space-y-6">
              <div>
                <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">任務標題</label>
                <input v-model="newTask.title" type="text" placeholder="要做什麼事？" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all">
              </div>
              <div>
                <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">關聯專案</label>
                <div class="relative">
                  <select v-model="newTask.project_id" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:border-blue-500 transition-all appearance-none cursor-pointer">
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                  <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-white/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">任務詳情</label>
                <textarea v-model="newTask.details" placeholder="更多任務說明..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all h-28 resize-none"></textarea>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">獎勵 (PTS)</label>
                  <input v-model="newTask.reward_points" type="number" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 transition-all">
                </div>
                <div>
                  <label class="block text-xs font-black text-white/40 uppercase mb-2 tracking-widest">截止日期</label>
                  <input v-model="newTask.due_date" type="date" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all cursor-pointer">
                </div>
              </div>
            </div>
            <div class="flex space-x-4 mt-10">
              <button @click="showTaskModal = false" class="flex-1 py-4 text-white/30 font-bold hover:text-white transition-all active:scale-95">取消</button>
              <button @click="createTask(newTask.status)" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-blue-600/20 active:scale-95">建立任務</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Review Modal -->
      <Transition name="modal">
        <div v-if="showReviewModal && selectedTask" class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 sm:pt-24">
          <div class="absolute inset-0 bg-black/70 backdrop-blur-md" @click="showReviewModal = false; selectedTask = null"></div>
          <div class="relative bg-gray-900 border border-white/10 w-full max-w-md rounded-[2.5rem] p-10 shadow-2xl shadow-yellow-500/5 max-h-[85vh] overflow-y-auto overflow-x-hidden text-center">
            <div class="w-20 h-20 bg-yellow-500/10 border border-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_30px_rgba(234,179,8,0.1)]">
              <span class="text-3xl text-yellow-400">✦</span>
            </div>
            <h2 class="text-3xl font-black text-white mb-2 tracking-tight">任務審核</h2>
            <p class="text-white/30 text-sm mb-8">請核對成果並決定是否發放獎勵</p>

            <div class="bg-white/5 border border-white/10 rounded-3xl p-6 mb-10 text-left">
              <span class="text-[10px] font-black uppercase tracking-widest text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-md mb-3 inline-block">
                {{ selectedTask.project?.name }}
              </span>
              <h4 class="font-bold text-white text-xl mb-2">{{ selectedTask.title }}</h4>
              <p class="text-white/40 text-sm leading-relaxed mb-6">{{ selectedTask.details }}</p>
              
              <div class="flex items-center justify-between border-t border-white/10 pt-5">
                <div class="flex items-center text-xs font-bold text-white/30 uppercase tracking-widest">
                  <svg class="w-4 h-4 mr-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                  {{ selectedTask.due_date ? new Date(selectedTask.due_date).toLocaleDateString() : 'No Date' }}
                </div>
                <div v-if="selectedTask.reward_points > 0" class="inline-flex items-center space-x-1.5 bg-yellow-500/20 border border-yellow-500/30 px-3 py-1 rounded-full text-xs text-yellow-400 font-black">
                  <span>✦</span> <span>{{ selectedTask.reward_points }} PTS</span>
                </div>
              </div>
            </div>

            <div class="flex flex-col space-y-3">
              <div class="flex space-x-3">
                <button @click="handleReject" class="flex-1 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 font-black py-4 rounded-2xl transition-all active:scale-95">
                  ✘ 退回修正
                </button>
                <button @click="handleApprove" class="flex-2 bg-green-600 hover:bg-green-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-green-600/20 active:scale-95">
                  ✔ 審核通過並給點
                </button>
              </div>
              <button @click="showReviewModal = false; selectedTask = null" class="py-2 text-white/20 font-bold hover:text-white/40 transition-all text-xs uppercase tracking-widest">
                稍後處理
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Department Management Modal -->
      <Transition name="modal">
        <div v-if="showDeptModal" class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 sm:pt-24">
          <div class="absolute inset-0 bg-black/70 backdrop-blur-md" @click="showDeptModal = false"></div>
          <div class="relative bg-gray-900 border border-white/10 w-full max-w-2xl rounded-[2.5rem] p-10 shadow-2xl max-h-[85vh] overflow-y-auto">
            <h2 class="text-3xl font-black text-white mb-8 tracking-tight flex items-center">
               <svg class="w-8 h-8 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-width="2"></path></svg>
               部門管理系統
            </h2>

            <!-- Form: Create/Edit -->
            <div class="bg-white/5 border border-white/10 rounded-3xl p-6 mb-10">
              <h3 class="text-blue-400 font-bold mb-4 uppercase tracking-widest text-xs">{{ selectedDept ? '編輯部門' : '新增部門' }}</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input v-model="newDept.name" type="text" placeholder="部門名稱 (如: 視覺部)" class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-blue-500 transition">
                <input v-model="newDept.code" type="text" placeholder="識別碼 (如: VISUAL)" class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-blue-500 transition">
              </div>
              <textarea v-model="newDept.description" placeholder="部門簡述..." class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-blue-500 mt-4 h-20 resize-none"></textarea>
              <div class="flex justify-end mt-4">
                <button @click="saveDept" class="bg-blue-600 hover:bg-blue-500 text-white font-black px-8 py-3 rounded-xl transition shadow-lg shadow-blue-600/20 active:scale-95">
                  {{ selectedDept ? '更新資訊' : '建立部門' }}
                </button>
              </div>
            </div>

            <!-- List: All Departments -->
            <div class="space-y-3">
              <h3 class="text-white/40 font-bold uppercase tracking-widest text-xs ml-2">現有事業組</h3>
              <div v-for="dept in departments" :key="dept.id" class="flex items-center justify-between bg-white/5 border border-white/5 hover:border-white/10 p-4 rounded-2xl transition-all group">
                <div>
                  <div class="flex items-center space-x-2">
                    <span class="text-white font-bold">{{ dept.name }}</span>
                    <span class="text-[10px] bg-white/10 text-white/50 px-2 py-0.5 rounded font-mono uppercase">{{ dept.code }}</span>
                  </div>
                  <p class="text-xs text-white/30 mt-1">{{ dept.description || '無描述' }}</p>
                </div>
                <div class="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openDeptModal(dept)" class="p-2 hover:bg-blue-500/20 text-blue-400 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"></path></svg>
                  </button>
                  <button @click="deleteDept(dept.id)" class="p-2 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"></path></svg>
                  </button>
                </div>
              </div>
            </div>

            <button @click="showDeptModal = false" class="w-full mt-10 py-4 text-white/20 font-bold hover:text-white transition-all uppercase text-xs tracking-widest border-t border-white/5">
              關閉管理面板
            </button>
          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
/* Modal Transition */
.modal-enter-active, .modal-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.9) translateY(20px);
}

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>
