<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

const auth = useAuthStore()
const router = useRouter()
const projects = ref<any[]>([])
const departments = ref<any[]>([])
const loading = ref(true)

// Modals State
const showEditModal = ref(false)
const selectedProject = ref<any>(null)
const isEditing = ref(false)
const showDeptDropdown = ref(false)

// Form State
const projectForm = ref({
  name: '',
  department: '',
  description: '',
  status: 'active'
})

const selectDepartment = (name: string) => {
  projectForm.value.department = name
  showDeptDropdown.value = false
}

const fetchData = async () => {
  loading.value = true
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    const [projRes, deptRes] = await Promise.all([
      axios.get('/api/projects', config),
      axios.get('/api/departments', config)
    ])
    
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
  selectedProject.value = null
  projectForm.value = { name: '', department: '', description: '', status: 'active' }
  showEditModal.value = true
}

const openEditModal = (project: any) => {
  isEditing.value = true
  selectedProject.value = project
  projectForm.value = {
    name: project.name,
    department: project.department,
    description: project.description,
    status: project.status
  }
  showEditModal.value = true
}

const saveProject = async () => {
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    if (isEditing.value && selectedProject.value) {
      await axios.put(`/api/projects/${selectedProject.value.id}`, projectForm.value, config)
    } else {
      await axios.post('/api/projects', projectForm.value, config)
    }
    showEditModal.value = false
    fetchData()
  } catch (err) { alert('儲存失敗') }
}

const deleteProject = async (id: number) => {
  if (!confirm('確定要刪除此專案嗎？這將會刪除該專案下的所有任務。')) return
  try {
    await axios.delete(`/api/projects/${id}`, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
  } catch (err) { alert('刪除失敗') }
}

const handleLogout = () => { auth.logout(); router.push('/login') }

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed">
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
            <router-link to="/projects" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all bg-blue-600 text-white shadow-lg shadow-blue-600/20">專案管理</router-link>
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

      <!-- Main Controls -->
      <header class="p-8 pb-0 max-w-[1600px] mx-auto flex justify-between items-end">
        <div>
          <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">專案資產維護</h1>
          <p class="text-blue-100/60 font-medium tracking-wide">管理各事業組的核心開發計畫與生命週期</p>
        </div>
        <button @click="openCreateModal" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-10 rounded-2xl shadow-xl shadow-blue-500/30 transition-all transform hover:-translate-y-1 active:scale-95 text-sm uppercase tracking-widest">+ 啟動新計畫</button>
      </header>

      <!-- Project List (Aligned Scaling with Users) -->
      <main class="p-8 max-w-[1600px] mx-auto">
        <div v-if="loading" class="flex justify-center py-40">
          <div class="animate-spin rounded-full h-16 w-16 border-4 border-white/20 border-t-blue-500"></div>
        </div>
        <div v-else class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-black/40">
          <div class="p-6 border-b border-white/5 bg-white/5 flex justify-between items-center">
            <h3 class="font-black text-white text-lg tracking-tighter uppercase ml-2">現有專案清單</h3>
            <span class="bg-white/10 text-white text-xs font-bold px-4 py-1 rounded-full border border-white/5">{{ projects.length }} 個計畫</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="text-[10px] font-black uppercase tracking-[0.25em] text-white/30 border-b border-white/5">
                  <th class="px-10 py-5">專案資訊</th>
                  <th class="px-10 py-5">事業組</th>
                  <th class="px-10 py-5">運行狀態</th>
                  <th class="px-10 py-5">計畫描述</th>
                  <th class="px-10 py-5 text-right">管理操作</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5 text-white">
                <tr v-for="project in projects" :key="project.id" class="hover:bg-white/5 transition-all group">
                  <td class="px-10 py-8">
                    <div class="flex items-center space-x-5">
                      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center font-black text-xl border border-white/20 shadow-lg text-white">
                        {{ project.name.substring(0,1).toUpperCase() }}
                      </div>
                      <p class="font-black text-white text-lg group-hover:text-blue-300 transition-colors">{{ project.name }}</p>
                    </div>
                  </td>
                  <td class="px-10 py-8">
                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-300 bg-blue-500/10 px-3 py-1 rounded-lg border border-blue-500/20">
                      {{ project.department }}
                    </span>
                  </td>
                  <td class="px-10 py-8">
                    <div class="flex items-center space-x-2">
                       <div class="w-2 h-2 rounded-full" :class="project.status === 'active' ? 'bg-green-400 shadow-[0_0_8px_rgba(74,222,128,0.6)]' : 'bg-gray-500'"></div>
                       <span class="text-xs font-black uppercase tracking-widest" :class="project.status === 'active' ? 'text-green-400' : 'text-white/40'">{{ project.status }}</span>
                    </div>
                  </td>
                  <td class="px-10 py-8 text-white/40 text-xs font-medium italic">
                    {{ project.description || '無詳細說明' }}
                  </td>
                  <td class="px-10 py-8 text-right">
                    <div class="flex justify-end items-center space-x-3">
                      <button @click="openEditModal(project)" class="bg-white/10 hover:bg-white/20 text-white font-black text-[10px] px-6 py-2.5 rounded-xl transition-all uppercase tracking-widest border border-white/10">管理</button>
                      <button @click="deleteProject(project.id)" class="p-2.5 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white border border-red-500/20 rounded-xl transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"></path></svg></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>

      <!-- Edit Modal (Top-aligned Precision) -->
      <Transition name="modal">
        <div v-if="showEditModal" class="fixed inset-0 z-[100] flex items-start justify-center p-4 pt-10 sm:pt-24 overflow-hidden">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showEditModal = false"></div>
          <div class="relative z-10 bg-gray-900 border border-white/10 w-full max-w-md rounded-[3rem] p-12 shadow-2xl max-h-[85vh] overflow-y-auto custom-scrollbar text-white">
            <h2 class="text-3xl font-black text-white mb-2 tracking-tight">計畫配置設定</h2>
            <p class="text-white/30 text-sm mb-10 italic">請調整專案的部屬細節與運行狀態</p>
            <div class="space-y-8">
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">計畫名稱</label>
                <input v-model="projectForm.name" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold outline-none focus:border-blue-500 transition-all">
              </div>
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">負責事業組</label>
                <div class="relative">
                  <div @click="showDeptDropdown = !showDeptDropdown" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all"><span :class="projectForm.department ? 'text-white font-bold' : 'text-white/20'">{{ projectForm.department || '選擇事業組' }}</span><svg class="w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg></div>
                  <div v-if="showDeptDropdown" class="absolute z-[110] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-60 overflow-y-auto custom-scrollbar">
                    <div @click="selectDepartment('')" class="px-5 py-4 text-white/40 hover:bg-white/5 cursor-pointer text-xs italic border-b border-white/5">未分配</div>
                    <div v-for="dept in departments" :key="dept.id" @click="selectDepartment(dept.name)" class="px-5 py-4 text-white font-black hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0">{{ dept.name }}</div>
                  </div></div></div>
              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">計畫狀態</label>
                <div class="grid grid-cols-3 gap-3">
                   <button v-for="st in ['active', 'archived', 'completed']" :key="st" @click="projectForm.status = st" class="py-3 rounded-xl font-black text-[10px] uppercase border transition-all" :class="projectForm.status === st ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-600/20' : 'bg-white/5 border-white/10 text-white/30 hover:bg-white/10'">{{ st }}</button>
                </div>
              </div>
              <div><label class="block text-[10px] font-black text-white/40 uppercase mb-3 tracking-widest ml-1">計畫描述</label><textarea v-model="projectForm.description" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-medium outline-none focus:border-blue-500 transition-all h-24 resize-none"></textarea></div>
            </div>
            <div class="flex space-x-4 mt-12 pt-8 border-t border-white/5">
              <button @click="showEditModal = false" class="flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs">取消</button>
              <button @click="saveProject" class="flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-blue-600/40 active:scale-95 uppercase tracking-widest text-sm">{{ isEditing ? '更新計畫' : '啟動計畫' }}</button>
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
