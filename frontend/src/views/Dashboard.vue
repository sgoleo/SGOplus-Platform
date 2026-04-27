<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import draggable from 'vuedraggable'
import Navbar from '../components/Navbar.vue'
import TaskDetailModal from '../components/TaskDetailModal.vue'
import axios from 'axios'

const auth = useAuthStore()
const tasks = ref<any[]>([])
const projects = ref<any[]>([])
const departments = ref<any[]>([])
const loading = ref(true)

// Personal Points Info
const personalPointsInfo = ref({ points: 0, limit: 3 })

// Modals State
const showProjectModal = ref(false)
const showTaskModal = ref(false)
const showReviewModal = ref(false)
const showDeptModal = ref(false)
const showEvidenceModal = ref(false)
const selectedTask = ref<any>(null)
const selectedDept = ref<any>(null)
const showDetail = ref(false)
const detailTask = ref<any>(null)

// Evidence Form State
const evidenceForm = ref({
  text: '',
  image: null as File | null
})
const evidencePreview = ref('')

const newTask = ref({ 
  title: '', 
  details: '', 
  project_id: '', 
  department: '',
  status: 'Open', 
  due_date: '', 
  reward_points: 1,
  type: 'personal' 
})
const fileInput = ref<HTMLInputElement | null>(null)
const newProject = ref({ name: '', department: '', description: '' })
const newDept = ref({ name: '', code: '', description: '' })

// Custom Dropdown Logic
const dropdowns = ref({
  projectDept: false,
  taskProject: false,
  taskDept: false
})

const selectDepartment = (dept: string) => {
  newProject.value.department = dept
  dropdowns.value.projectDept = false
}

const selectTaskProject = (project: any) => {
  newTask.value.project_id = project.id
  dropdowns.value.taskProject = false
}

// Kanban Columns State
const columnData = ref<any[]>([
  { id: 'Open', title: '待辦事項', icon: '📝', tasks: [] },
  { id: 'In Progress', title: '進行中', icon: '🚀', tasks: [] },
  { id: 'Review', title: '待審核', icon: '⚖️', tasks: [] },
  { id: 'Done', title: '已完成', icon: '✅', tasks: [] }
])

const updateColumns = () => {
  const isAdmin = auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')

  columnData.value[0].tasks = tasks.value.filter(t => {
      const s = (t.current_user_status || t.status || '').toLowerCase()
      // 如果是管理員，只要主狀態是 Open 且沒人開工，就在這裡
      // 但其實只要 current_user_status 是 open 且沒被後面的 In Progress 攔截即可
      return s === 'open'
  })
  
  columnData.value[1].tasks = tasks.value.filter(t => {
      // 管理員看到任何人正在進行中的任務
      if (isAdmin && t.assignees?.some((a: any) => a.pivot_status === 'in_progress')) return true
      const s = (t.current_user_status || t.status || '').toLowerCase()
      return s === 'in progress' || s === 'in_progress'
  })

  columnData.value[2].tasks = tasks.value.filter(t => {
      const s = (t.current_user_status || '').toLowerCase()
      if (s === 'review' || s === 'pending_review') return true;
      if (isAdmin) {
          // 管理員看到任何人提交的審核
          return t.assignees?.some((a: any) => a.pivot_status === 'pending_review' || a.pivot_status === 'Review') || t.status === 'Review';
      }
      return false;
  })

  columnData.value[3].tasks = tasks.value.filter(t => {
      const s = (t.current_user_status || t.status || '').toLowerCase()
      return s === 'done' || s === 'completed'
  })
}

const fetchData = async () => {
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    const [taskRes, projectRes, userRes, deptRes, pointsRes] = await Promise.all([
      axios.get('/api/tasks', config),
      axios.get('/api/projects', config),
      axios.get('/api/user', config),
      axios.get('/api/departments', config),
      axios.get('/api/user/personal-points', config)
    ])
    
    tasks.value = Array.isArray(taskRes.data) ? taskRes.data : (taskRes.data.data || taskRes.data.value || [])
    projects.value = Array.isArray(projectRes.data) ? projectRes.data : (projectRes.data.data || projectRes.data.value || [])
    
    const userData = userRes.data.value || userRes.data
    auth.user.points = userData.points
    
    const deptData = deptRes.data
    const rawDepts = Array.isArray(deptData) ? deptData : (deptData.value || deptData.data || [])
    departments.value = [{ id: 'public', name: 'Public' }, ...rawDepts]

    personalPointsInfo.value = pointsRes.data
    
    updateColumns()
  } catch (err) {
    console.error('Failed to fetch data', err)
  } finally {
    loading.value = false
  }
}

const handleFileChange = (e: any) => {
  const file = e.target.files[0]
  if (file) {
    evidenceForm.value.image = file
    evidencePreview.value = URL.createObjectURL(file)
  }
}

const submitReview = async () => {
  if (!evidenceForm.value.image) return alert('請上傳完成證明圖片')
  if (!selectedTask.value) return

  const formData = new FormData()
  formData.append('evidence_image', evidenceForm.value.image)
  formData.append('evidence_text', evidenceForm.value.text)

  try {
    await axios.post(`/api/tasks/${selectedTask.value.id}/submit-review`, formData, {
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        'Content-Type': 'multipart/form-data'
      }
    })
    showEvidenceModal.value = false
    evidenceForm.value = { text: '', image: null }
    evidencePreview.value = ''
    fetchData()
  } catch (err) {
    alert('提交失敗')
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
  // Always allow viewing details on click
  detailTask.value = task
  showDetail.value = true
}

const handleApprove = async (userId: number, taskId?: number) => {
  const tid = taskId || selectedTask.value?.id
  if (!tid) return;
  try {
    await axios.post(`/api/tasks/${tid}/approve`, {
      user_id: userId
    }, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
    // 檢查是否還有待審核的成員
    const updatedTask = tasks.value.find(t => t.id === tid)
    const remaining = updatedTask?.assignees?.filter((a: any) => a.pivot_status === 'pending_review') || []
    if (remaining.length === 0) {
      showReviewModal.value = false
      selectedTask.value = null
    }
  } catch (err) { alert('審核失敗') }
}

const handleReject = async (userId: number) => {
  if (!selectedTask.value) return;
  try {
    await axios.post(`/api/tasks/${selectedTask.value.id}/reject`, {
      user_id: userId
    }, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
    const updatedTask = tasks.value.find(t => t.id === selectedTask.value.id)
    const remaining = updatedTask?.assignees?.filter((a: any) => a.pivot_status === 'pending_review') || []
    if (remaining.length === 0) {
      showReviewModal.value = false
      selectedTask.value = null
    }
  } catch (err) { alert('退回失敗') }
}

const handleStatusChange = async (event: any, newStatus: string) => {
  if (event.added) {
    const task = event.added.element
    
    // 如果是個人任務且移動到 Done，自動觸發 self-approval
    if (task.type === 'personal' && newStatus === 'Done') {
        handleApprove(auth.user.id, task.id)
        return
    }

    if (newStatus === 'Review' || newStatus === 'pending_review') {
      selectedTask.value = task
      showEvidenceModal.value = true
      return
    }
    try {
      await axios.put(`/api/tasks/${task.id}`, { status: newStatus }, {
        headers: { Authorization: `Bearer ${auth.token}` }
      })
      fetchData()
    } catch (err) {
      console.error('Update failed', err)
      fetchData()
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

const createTask = async () => {
  try {
    // If user is not admin, we need a default project if none selected
    if (!newTask.value.project_id && projects.value.length > 0) {
        newTask.value.project_id = projects.value[0].id
    }

    const selectedProject = projects.value.find(p => p.id === parseInt(newTask.value.project_id))
    await axios.post('/api/tasks', {
      ...newTask.value,
      department: newTask.value.department || selectedProject?.department || auth.user.department || 'Public'
    }, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    showTaskModal.value = false
    newTask.value = { 
      title: '', 
      details: '', 
      project_id: '', 
      department: '',
      status: 'Open', 
      due_date: '', 
      reward_points: 1,
      type: 'personal' 
    }
    fetchData()
  } catch (err: any) { 
    alert(err.response?.data?.message || '建立任務失敗') 
  }
}

const openTaskModal = (status: string) => {
  const isAdmin = auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')
  newTask.value = { 
    title: '', 
    details: '', 
    project_id: '', 
    department: auth.user.department || 'Public',
    status: status, 
    due_date: '', 
    reward_points: isAdmin ? 0 : 1,
    type: isAdmin ? 'official' : 'personal' 
  }
  showTaskModal.value = true
}

const activeColumnIdx = ref(0)

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed font-sans overflow-x-hidden">
    <div class="min-h-screen backdrop-blur-sm bg-black/20 overflow-x-hidden">
      
      <Navbar />

      <!-- Dashboard Header -->
      <header class="p-4 md:p-8 max-w-[1600px] mx-auto">
        <div class="flex flex-col md:flex-row md:justify-between md:items-end space-y-4 md:space-y-0">
          <div>
            <h1 class="text-3xl md:text-5xl font-black text-white mb-1 drop-shadow-lg tracking-tight">任務看板</h1>
            <div class="flex items-center space-x-3">
              <p class="text-blue-100/60 font-medium text-sm md:text-base">管理您的專案與團隊進度</p>
              <div class="bg-blue-500/20 border border-blue-500/30 px-3 py-1 rounded-full flex items-center space-x-2">
                <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">今日個人任務額度</span>
                <span class="text-[10px] font-black text-white">{{ personalPointsInfo.points }} / {{ personalPointsInfo.limit }} PTS</span>
              </div>
            </div>
          </div>
          <div class="flex space-x-2 md:space-x-4">
            <button v-if="auth.roles.includes('SuperAdmin')" 
                    @click="openDeptModal()" 
                    class="flex-1 md:flex-none bg-white/10 hover:bg-white/20 text-white border border-white/10 font-bold py-2.5 md:py-3 px-4 md:px-6 rounded-2xl transition-all flex items-center justify-center space-x-2 text-sm">
              <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
              <span>部門管理</span>
            </button>
            
            <button v-if="auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')" @click="showProjectModal = true" class="flex-2 md:flex-none bg-blue-500 hover:bg-blue-600 text-white font-black py-2.5 md:py-3 px-6 md:px-8 rounded-2xl shadow-xl shadow-blue-500/20 transition-all transform active:scale-95 text-sm md:text-base">
              + 建立新專案
            </button>
            <button v-else @click="openTaskModal('Open')" class="flex-2 md:flex-none bg-blue-500 hover:bg-blue-600 text-white font-black py-2.5 md:py-3 px-6 md:px-8 rounded-2xl shadow-xl shadow-blue-500/20 transition-all transform active:scale-95 text-sm md:text-base">
              + 建立個人任務
            </button>
          </div>
        </div>
      </header>

      <!-- Mobile Column Selector Tabs -->
      <div class="lg:hidden px-4 mb-4">
        <div class="bg-black/20 backdrop-blur-md p-1 rounded-2xl border border-white/10 flex overflow-x-auto no-scrollbar">
          <button v-for="(col, idx) in columnData" :key="col.id"
                  @click="activeColumnIdx = idx"
                  class="flex-1 min-w-[90px] px-3 py-2.5 rounded-xl text-[11px] font-black transition-all whitespace-nowrap flex flex-col items-center justify-center space-y-1"
                  :class="[activeColumnIdx === idx ? 'bg-blue-600 text-white shadow-lg' : 'text-white/40 hover:text-white/60']">
            <span class="text-lg">{{ col.icon }}</span>
            <span>{{ col.title }}</span>
          </button>
        </div>
      </div>

      <!-- Kanban Area -->
      <main class="p-4 md:p-8 max-w-[1600px] mx-auto overflow-hidden">
        <div v-if="loading" class="flex justify-center py-40">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-white/20 border-t-blue-400"></div>
        </div>

        <div v-else class="relative">
          <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 md:gap-8 items-start">
            <div v-for="(col, idx) in columnData" :key="col.id" 
                 class="flex flex-col h-full transition-all duration-300"
                 :class="[
                   activeColumnIdx === idx ? 'block animate-fade-in' : 'hidden lg:flex'
                 ]">
              <div class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2rem] overflow-hidden flex flex-col h-full shadow-2xl">
                <div class="p-5 md:p-6 border-b border-white/5 flex justify-between items-center bg-white/5">
                  <div class="flex items-center space-x-3">
                    <span class="text-2xl">{{ col.icon }}</span>
                    <h3 class="font-black text-white text-lg tracking-tight uppercase">{{ col.title }}</h3>
                  </div>
                  <span class="bg-white/10 text-white text-xs font-black px-3 py-1 rounded-full border border-white/10">{{ col.tasks.length }}</span>
                </div>

                <draggable 
                  class="flex-1 p-3 md:p-4 space-y-3 md:space-y-4 overflow-y-auto max-h-[calc(100vh-350px)] lg:max-h-[600px] min-h-[200px] custom-scrollbar"
                  v-model="col.tasks"
                  group="tasks"
                  item-key="id"
                  @change="(e: any) => handleStatusChange(e, col.id)"
                  :animation="250"
                  ghost-class="opacity-50"
                  drag-class="rotate-2"
                >
                  <template #item="{ element }">
                    <div @click="openReviewModal(element)"
                         class="group relative bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/30 p-4 md:p-5 rounded-2xl transition-all shadow-lg overflow-hidden cursor-pointer"
                         :class="[
                           col.id === 'Review' && (auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')) ? 'hover:bg-blue-500/10 hover:border-blue-400/50' : '',
                           element.type === 'personal' ? 'border-purple-500/20 bg-purple-500/5' : ''
                         ]">
                      
                      <!-- Visual Drag Handle -->
                      <div class="absolute top-0 left-0 bottom-0 w-1 transition-opacity" :class="element.type === 'personal' ? 'bg-purple-500 opacity-100' : 'bg-blue-500 opacity-0 group-hover:opacity-100'"></div>
                      
                      <div>
                        <div class="flex justify-between items-start mb-2 md:mb-3">
                          <div class="flex items-center space-x-2">
                             <span class="text-[9px] font-black uppercase tracking-widest text-blue-300/80 bg-blue-500/10 px-2 py-0.5 rounded-md border border-blue-500/10">
                              {{ element.project?.name || 'No Project' }}
                            </span>
                            <span v-if="element.type === 'personal'" class="text-[9px] font-black uppercase tracking-widest text-purple-300 bg-purple-500/20 px-2 py-0.5 rounded-md border border-purple-500/30">
                              個人任務
                            </span>
                          </div>
                          <div class="opacity-20 group-hover:opacity-40 transition-opacity">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M7 7h2v2H7V7zm0 4h2v2H7v-2zm0 4h2v2H7v-2zm4-8h2v2h-2V7zm0 4h2v2h-2v-2zm0 4h2v2h-2v-2z"></path></svg>
                          </div>
                        </div>
                        <h4 class="font-bold text-white text-base md:text-lg mb-1 md:mb-2 leading-tight group-hover:text-blue-200 transition-colors">{{ element.title }}</h4>
                        <p class="text-white/40 text-[11px] md:text-xs line-clamp-2 leading-relaxed mb-4">{{ element.details }}</p>
                        
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                          <div v-if="element.reward_points > 0" class="inline-flex items-center space-x-1.5 bg-yellow-500/10 border border-yellow-500/20 px-2.5 py-0.5 rounded-full text-[10px] text-yellow-400 font-black">
                            <span>✦</span> <span>{{ element.reward_points }} PTS</span>
                          </div>
                          <div v-if="element.current_user_status === 'Review' || element.current_user_status === 'pending_review'" class="inline-flex items-center space-x-1 bg-gray-500/20 border border-gray-500/30 px-2.5 py-0.5 rounded-full text-[10px] text-gray-300 font-black">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2.5"></path></svg>
                            <span>審核中</span>
                          </div>
                        </div>

                        <div class="flex items-center justify-between mt-auto">
                          <div class="flex items-center text-[10px] font-bold text-white/20 uppercase tracking-widest">
                            <svg class="w-3 h-3 mr-1.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2.5"></path></svg>
                            {{ element.due_date ? new Date(element.due_date).toLocaleDateString() : 'No Deadline' }}
                          </div>
                          
                          <!-- Action Button -->
                          <div class="flex space-x-2">
                             <button v-if="element.type === 'personal' && (element.current_user_status === 'Review' || element.current_user_status === 'pending_review' || element.status === 'Review')"
                                  @click.stop="handleApprove(auth.user.id, element.id)"
                                  class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-90 shadow-lg shadow-purple-500/20">
                                自行核准
                             </button>
                             
                             <button v-if="element.current_user_status === 'Open' || element.current_user_status === 'In Progress'"
                                  @click.stop="selectedTask = element; showEvidenceModal = true"
                                  class="w-9 h-9 bg-green-500/10 hover:bg-green-500/30 border border-green-500/30 text-green-400 rounded-full flex items-center justify-center transition-all active:scale-90 relative z-10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                             </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>
                </draggable>

                <button @click="openTaskModal(col.id)" class="m-3 md:m-4 p-4 rounded-2xl bg-white/5 border border-dashed border-white/10 text-white/30 text-xs md:text-sm font-black hover:bg-white/10 hover:text-white transition-all flex items-center justify-center uppercase tracking-widest">
                  + 新增任務
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- Modals -->
      <Transition name="modal">
        <div v-if="showProjectModal" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showProjectModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-md rounded-t-[2.5rem] sm:rounded-[2.5rem] p-8 md:p-10 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-8 tracking-tight">建立新專案</h2>
            <div class="space-y-6">
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">專案名稱</label>
                <input v-model="newProject.name" type="text" placeholder="輸入專案名稱..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all placeholder:text-white/10">
              </div>
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">所屬部門</label>
                <div class="relative">
                  <div @click="dropdowns.projectDept = !dropdowns.projectDept" 
                       class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                    <span :class="newProject.department ? 'text-white font-bold' : 'text-white/20'">{{ newProject.department || '選擇部門' }}</span>
                    <svg class="w-5 h-5 text-white/20 transition-transform" :class="{'rotate-180': dropdowns.projectDept}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" stroke-linecap="round"></path></svg>
                  </div>
                  <div v-if="dropdowns.projectDept" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl max-h-60 overflow-y-auto custom-scrollbar">
                    <div v-for="dept in departments" :key="dept.id" @click="selectDepartment(dept.name)" class="px-6 py-4 text-white font-bold hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0">{{ dept.name }}</div>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">專案描述</label>
                <textarea v-model="newProject.description" placeholder="簡述此專案的目標..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white outline-none focus:border-blue-500 transition-all h-28 resize-none placeholder:text-white/10 text-sm md:text-base"></textarea>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 mt-10">
              <button @click="showProjectModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all text-sm">取消</button>
              <button @click="createProject" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs">建立專案</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Task Modal -->
      <Transition name="modal">
        <div v-if="showTaskModal" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showTaskModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-md rounded-t-[2.5rem] sm:rounded-[2.5rem] p-8 md:p-10 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-8 tracking-tight">{{ auth.roles.includes('SuperAdmin') ? '建立新任務' : '建立個人任務' }}</h2>
            <div class="space-y-6">
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">任務名稱</label>
                <input v-model="newTask.title" type="text" placeholder="輸入任務名稱..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all">
              </div>
              <div v-if="auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')">
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">任務類型</label>
                <select v-model="newTask.type" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all appearance-none">
                  <option value="official" class="bg-gray-900">系統任務 (Official)</option>
                  <option value="personal" class="bg-gray-900">個人任務 (Personal)</option>
                </select>
              </div>
              <div v-if="auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects')">
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">執行部門 / 範圍</label>
                <div class="relative">
                  <div @click="dropdowns.taskDept = !dropdowns.taskDept" 
                       class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                    <span :class="newTask.department ? 'text-white font-bold' : 'text-white/20'">{{ newTask.department || '選擇部門' }}</span>
                    <svg class="w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" stroke-linecap="round"></path></svg>
                  </div>
                  <div v-if="dropdowns.taskDept" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl max-h-40 overflow-y-auto custom-scrollbar">
                    <div v-for="d in departments" :key="d.id" @click="newTask.department = d.name; dropdowns.taskDept = false" class="px-6 py-4 text-white font-bold hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0">{{ d.name }}</div>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">關聯專案</label>
                <div class="relative">
                  <div @click="dropdowns.taskProject = !dropdowns.taskProject" 
                       class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                    <span :class="newTask.project_id ? 'text-white font-bold' : 'text-white/20'">{{ projects.find(p=>p.id === parseInt(newTask.project_id))?.name || '不關聯專案 (獨立任務)' }}</span>
                    <svg class="w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" stroke-linecap="round"></path></svg>
                  </div>
                  <div v-if="dropdowns.taskProject" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl max-h-40 overflow-y-auto custom-scrollbar">
                    <div v-for="p in projects" :key="p.id" @click="selectTaskProject(p)" class="px-6 py-4 text-white font-bold hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0">{{ p.name }}</div>
                  </div>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">獎勵點數 (上限 1)</label>
                  <input v-model="newTask.reward_points" type="number" :max="auth.roles.includes('SuperAdmin') ? 999 : 1" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-yellow-400 font-black outline-none focus:border-yellow-500 transition-all">
                </div>
                <div>
                  <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">截止日期</label>
                  <input v-model="newTask.due_date" type="date" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all">
                </div>
              </div>
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">詳細說明</label>
                <textarea v-model="newTask.details" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white outline-none focus:border-blue-500 transition-all h-24 resize-none"></textarea>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 mt-10">
              <button @click="showTaskModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all text-sm">取消</button>
              <button @click="createTask" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs">建立任務</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Review Modal -->
      <Transition name="modal">
        <div v-if="showReviewModal" class="fixed inset-0 z-[300] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showReviewModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-2xl rounded-t-[3rem] sm:rounded-[3rem] p-8 md:p-10 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h2 class="text-2xl md:text-3xl font-black text-white mb-6">任務成果審核</h2>
            <div v-if="selectedTask" class="space-y-8">
              <div v-for="assignee in selectedTask.assignees.filter((a: any) => a.pivot_status === 'pending_review')" :key="assignee.id" class="bg-white/5 border border-white/10 rounded-3xl p-6">
                <div class="flex items-center justify-between mb-6">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center font-bold text-white">{{ assignee.name.charAt(0) }}</div>
                    <div>
                      <p class="font-bold text-white">{{ assignee.name }}</p>
                      <p class="text-[10px] text-white/40 uppercase tracking-widest">{{ assignee.department }}</p>
                    </div>
                  </div>
                </div>
                
                <div v-if="assignee.pivot_evidence_image" class="mb-6 rounded-2xl overflow-hidden border border-white/10">
                  <img :src="'/storage/' + assignee.pivot_evidence_image" class="w-full h-auto max-h-60 object-contain bg-black/40">
                </div>
                
                <div class="mb-8">
                  <p class="text-[10px] font-black text-white/40 uppercase mb-2">提交說明</p>
                  <p class="text-white/80 text-sm italic">{{ assignee.pivot_evidence_text || '無說明' }}</p>
                </div>

                <div class="flex space-x-3">
                  <button @click="handleReject(assignee.id)" class="flex-1 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-xl font-bold transition-all text-xs uppercase tracking-widest">駁回</button>
                  <button @click="handleApprove(assignee.id)" class="flex-2 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl font-black transition-all shadow-lg text-xs uppercase tracking-widest">准予完成 (+{{ selectedTask.reward_points }} PTS)</button>
                </div>
              </div>
            </div>
            <button @click="showReviewModal = false" class="w-full mt-8 py-4 text-white/20 font-bold hover:text-white transition-all uppercase text-[10px] tracking-widest">關閉</button>
          </div>
        </div>
      </Transition>

      <!-- Evidence Submission Modal -->
      <Transition name="modal">
        <div v-if="showEvidenceModal" class="fixed inset-0 z-[300] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showEvidenceModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-md rounded-t-[3rem] sm:rounded-[3rem] p-8 md:p-10 shadow-2xl">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-2 tracking-tight">提交完成證明</h2>
            <p class="text-blue-400 text-[10px] font-black uppercase tracking-widest mb-8">任務: {{ selectedTask?.title }}</p>

            <div class="space-y-6">
              <div class="relative">
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">成果截圖 / 相片</label>
                <div @click="fileInput?.click()" class="w-full h-40 md:h-48 bg-white/5 border-2 border-dashed border-white/10 rounded-[2rem] flex flex-col items-center justify-center cursor-pointer hover:bg-white/10 transition-all overflow-hidden group">
                  <template v-if="!evidencePreview">
                    <svg class="w-10 h-10 text-white/20 group-hover:text-blue-400 transition-colors mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"></path></svg>
                    <p class="text-[10px] font-black text-white/20 uppercase">點擊上傳圖片</p>
                  </template>
                  <img v-else :src="evidencePreview" class="w-full h-full object-cover">
                </div>
                <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleFileChange">
              </div>

              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">完成說明 (選填)</label>
                <textarea v-model="evidenceForm.text" placeholder="請簡述您的任務成果..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white text-sm outline-none focus:border-blue-500 transition-all h-24 resize-none"></textarea>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-10">
              <button @click="showEvidenceModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs">取消</button>
              <button @click="submitReview" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs">確認提交審核</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Department Modal (Admin) -->
      <Transition name="modal">
        <div v-if="showDeptModal" class="fixed inset-0 z-[400] flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showDeptModal = false"></div>
          <div class="relative bg-gray-900 border-t sm:border border-white/10 w-full max-w-2xl rounded-t-[3rem] sm:rounded-[3.5rem] p-6 md:p-10 shadow-2xl max-h-[95vh] overflow-y-auto">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-8 tracking-tight flex items-center">
               <svg class="w-7 h-7 md:w-8 md:h-8 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-width="2"></path></svg>
               部門管理系統
            </h2>

            <div class="bg-white/5 border border-white/10 rounded-3xl p-5 md:p-6 mb-8">
              <h3 class="text-blue-400 font-black mb-4 uppercase tracking-widest text-[10px]">{{ selectedDept ? '編輯部門' : '新增部門' }}</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input v-model="newDept.name" type="text" placeholder="部門名稱 (如: 視覺部)" class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-blue-500 transition text-sm">
                <input v-model="newDept.code" type="text" placeholder="識別碼 (如: VISUAL)" class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-blue-500 transition text-sm">
              </div>
              <textarea v-model="newDept.description" placeholder="部門簡述..." class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-blue-500 mt-4 h-20 resize-none text-sm"></textarea>
              <div class="flex justify-end mt-4">
                <button @click="saveDept" class="w-full md:w-auto bg-blue-600 hover:bg-blue-500 text-white font-black px-8 py-3 rounded-xl transition shadow-lg active:scale-95 text-xs uppercase tracking-widest">
                  {{ selectedDept ? '更新資訊' : '建立部門' }}
                </button>
              </div>
            </div>

            <div class="space-y-3">
              <h3 class="text-white/40 font-black uppercase tracking-widest text-[10px] ml-2">現有事業組</h3>
              <div v-for="dept in departments" :key="dept.id" class="flex items-center justify-between bg-white/5 border border-white/5 p-4 rounded-2xl transition-all group">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center space-x-2">
                    <span class="text-white font-bold truncate">{{ dept.name }}</span>
                    <span class="text-[9px] bg-white/10 text-white/50 px-2 py-0.5 rounded font-mono uppercase">{{ dept.code }}</span>
                  </div>
                  <p class="text-[11px] text-white/30 mt-1 truncate">{{ dept.description || '無描述' }}</p>
                </div>
                <div class="flex space-x-1 ml-4">
                  <button @click="openDeptModal(dept)" class="p-2 hover:bg-blue-500/20 text-blue-400 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"></path></svg>
                  </button>
                  <button @click="deleteDept(dept.id)" class="p-2 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"></path></svg>
                  </button>
                </div>
              </div>
            </div>

            <button @click="showDeptModal = false" class="w-full mt-10 py-4 text-white/20 font-bold hover:text-white transition-all uppercase text-[10px] tracking-[0.3em] border-t border-white/5">
              關閉管理面板
            </button>
          </div>
        </div>
      </Transition>

      <!-- Task Detail Modal -->
      <TaskDetailModal :show="showDetail" :task="detailTask" @close="showDetail = false">
        <template #actions>
          <!-- Show "Submit Proof" button if task is not Review or Done -->
          <button v-if="detailTask && (detailTask.current_user_status === 'Open' || detailTask.current_user_status === 'In Progress')"
                  @click="selectedTask = detailTask; showEvidenceModal = true; showDetail = false"
                  class="flex-1 bg-green-600 hover:bg-green-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs">
            提交完成證明
          </button>
          <!-- Show "Admin Review" button if applicable -->
          <button v-if="detailTask && detailTask.status === 'Review' && (auth.roles.includes('SuperAdmin') || auth.hasPermission('manage-projects'))"
                  @click="selectedTask = detailTask; showReviewModal = true; showDetail = false"
                  class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs">
            審核此任務
          </button>
          <!-- Personal Self Approve -->
          <button v-if="detailTask && detailTask.type === 'personal' && (detailTask.current_user_status === 'Review' || detailTask.current_user_status === 'pending_review')"
                  @click="handleApprove(auth.user.id, detailTask.id); showDetail = false"
                  class="flex-1 bg-purple-600 hover:bg-purple-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs">
            自行核准
          </button>
        </template>
      </TaskDetailModal>

    </div>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.9) translateY(20px);
}

@media (max-width: 640px) {
  .modal-enter-from, .modal-leave-to {
    transform: translateY(100%);
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
