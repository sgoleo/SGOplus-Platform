<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import Navbar from '../components/Navbar.vue'

const auth = useAuthStore()
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

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed overflow-x-hidden">
    <div class="min-h-screen backdrop-blur-sm bg-black/10 overflow-x-hidden">
      
      <Navbar />

      <!-- Main Controls -->
      <header class="p-4 md:p-8 pb-0 max-w-[1600px] mx-auto">
        <div class="flex flex-col md:flex-row md:justify-between md:items-end space-y-4 md:space-y-0">
          <div>
            <h1 class="text-3xl md:text-5xl font-black text-white mb-2 drop-shadow-lg tracking-tight">專案管理</h1>
            <p class="text-blue-100/60 font-medium tracking-wide text-sm md:text-base">管理各事業組的核心開發計畫與生命週期</p>
          </div>
          <button @click="openCreateModal" class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white font-black py-3 px-8 rounded-2xl shadow-xl shadow-blue-500/30 transition-all transform active:scale-95 text-xs uppercase tracking-widest">+ 啟動新計畫</button>
        </div>
      </header>

      <!-- Project List -->
      <main class="p-4 md:p-8 max-w-[1600px] mx-auto">
        <div v-if="loading" class="flex justify-center py-40">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-white/20 border-t-blue-500"></div>
        </div>
        
        <div v-else>
          <!-- Desktop View -->
          <div class="hidden lg:block bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl">
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

          <!-- Mobile View -->
          <div class="lg:hidden space-y-4">
            <div v-for="project in projects" :key="project.id" class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2rem] p-5 shadow-xl relative overflow-hidden group">
              <div class="flex items-center space-x-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center font-black text-xl border border-white/20 text-white shadow-lg">
                  {{ project.name.substring(0,1).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <h4 class="text-white font-black text-lg truncate">{{ project.name }}</h4>
                  <div class="flex items-center space-x-2 mt-0.5">
                    <div class="w-2 h-2 rounded-full" :class="project.status === 'active' ? 'bg-green-400' : 'bg-gray-500'"></div>
                    <span class="text-[9px] font-black uppercase tracking-widest" :class="project.status === 'active' ? 'text-green-400' : 'text-white/40'">{{ project.status }}</span>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-1">負責事業組</p>
                <span class="text-[10px] font-black text-blue-300 bg-blue-500/10 px-2 py-0.5 rounded border border-blue-500/10 block w-fit">
                  {{ project.department }}
                </span>
              </div>

              <div class="mb-5">
                <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-1">計畫描述</p>
                <p class="text-white/50 text-xs italic leading-relaxed line-clamp-2">
                  {{ project.description || '無詳細說明' }}
                </p>
              </div>

              <div class="flex items-center justify-end pt-4 border-t border-white/5 gap-2">
                <button @click="openEditModal(project)" class="flex-1 bg-white/10 hover:bg-white/20 text-white font-black text-[10px] py-2.5 rounded-xl transition-all border border-white/10 uppercase tracking-widest">管理計畫</button>
                <button @click="deleteProject(project.id)" class="p-2.5 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"></path></svg></button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- Edit Modal -->
      <Transition name="modal">
        <div v-if="showEditModal" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showEditModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-md rounded-t-[3rem] sm:rounded-[3rem] p-8 md:p-12 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar text-white">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-2 tracking-tight">計畫配置設定</h2>
            <p class="text-white/30 text-[10px] md:text-sm mb-10 italic">請調整專案的部屬細節與運行狀態</p>
            <div class="space-y-6 md:space-y-8">
              <div>
                <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">計畫名稱</label>
                <input v-model="projectForm.name" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-3 md:py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base">
              </div>
              <div>
                <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">負責事業組</label>
                <div class="relative">
                  <div @click="showDeptDropdown = !showDeptDropdown" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-3 md:py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                    <span :class="projectForm.department ? 'text-white font-bold' : 'text-white/20'" class="text-sm md:text-base">{{ projectForm.department || '選擇事業組' }}</span>
                    <svg class="w-5 h-5 text-white/20 transition-transform" :class="{'rotate-180': showDeptDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3"></path></svg>
                  </div>
                  <div v-if="showDeptDropdown" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-60 overflow-y-auto custom-scrollbar">
                    <div @click="selectDepartment('')" class="px-5 py-3 text-white/40 hover:bg-white/5 cursor-pointer text-xs italic border-b border-white/5">未分配</div>
                    <div v-for="dept in departments" :key="dept.id" @click="selectDepartment(dept.name)" class="px-5 py-3 text-white font-bold hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0 text-sm">{{ dept.name }}</div>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">計畫狀態</label>
                <div class="grid grid-cols-3 gap-2 md:gap-3">
                   <button v-for="st in ['active', 'archived', 'completed']" :key="st" @click="projectForm.status = st" class="py-2.5 md:py-3 rounded-xl font-black text-[9px] md:text-[10px] uppercase border transition-all" :class="projectForm.status === st ? 'bg-blue-600 border-blue-500 text-white shadow-lg' : 'bg-white/5 border-white/10 text-white/30 hover:bg-white/10'">{{ st }}</button>
                </div>
              </div>
              <div>
                <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">計畫描述</label>
                <textarea v-model="projectForm.description" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-3 md:py-4 text-white font-medium outline-none focus:border-blue-500 transition-all h-24 resize-none text-sm"></textarea>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 mt-10 md:mt-12 pt-6 md:pt-8 border-t border-white/5">
              <button @click="showEditModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs">取消</button>
              <button @click="saveProject" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs md:text-sm">{{ isEditing ? '更新計畫' : '啟動計畫' }}</button>
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
