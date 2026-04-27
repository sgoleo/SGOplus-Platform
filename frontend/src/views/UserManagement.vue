<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import Navbar from '../components/Navbar.vue'

const auth = useAuthStore()
const users = ref<any[]>([])
const allRoles = ref<string[]>([])
const rolesDetailed = ref<any[]>([])
const availablePermissions = ref<string[]>([])
const departments = ref<any[]>([])
const loading = ref(true)

// Modals State
const showEditModal = ref(false)
const showRoleSettingsModal = ref(false)
const showRoleEditModal = ref(false) // Create/Edit individual role
const selectedUser = ref<any>(null)
const selectedRole = ref<any>(null)
const isEditing = ref(false)
const showDeptDropdown = ref(false)
const showRoleDeptDropdown = ref(false)

// Form State
const userForm = ref({
  name: '',
  email: '',
  password: '',
  department: '',
  roles: [] as string[]
})

const roleForm = ref({
  name: '',
  department: '',
  description: '',
  icon: '',
  color: '',
  permissions: [] as string[]
})

const selectDepartment = (name: string) => {
  userForm.value.department = name
  showDeptDropdown.value = false
}

const selectRoleDepartment = (name: string) => {
  roleForm.value.department = name
  showRoleDeptDropdown.value = false
}

const pointAdjustment = ref({
  amount: 0,
  reason: ''
})

// Role Metadata for enhanced UI
const getRoleMetadata = (roleName: string) => {
  // Try to find the dynamic role data first
  const dynamicRole = rolesDetailed.value.find(r => r.name === roleName)
  if (dynamicRole && dynamicRole.description) {
    return {
      icon: dynamicRole.icon || '👤',
      desc: dynamicRole.description,
      color: dynamicRole.color || 'from-gray-500 to-gray-700'
    }
  }

  const meta: Record<string, { icon: string, desc: string, color: string }> = {
    'SuperAdmin': { icon: '🛡️', desc: '擁有系統最高權限，可管理所有部門、點數與成員。', color: 'from-purple-600 to-indigo-600' },
    'SGOstudio-Manager': { icon: '👔', desc: '負責 SGOstudio 的專案審核與任務指派。', color: 'from-blue-600 to-cyan-600' },
    'SGOstudio-Member': { icon: '🎨', desc: '執行 SGOstudio 的創意與設計任務。', color: 'from-blue-400 to-blue-600' },
    'Hardware-Manager': { icon: '⚒️', desc: '負責硬體研發部的專案規劃與團隊協調。', color: 'from-emerald-600 to-teal-600' },
    'Hardware-Member': { icon: '⚙️', desc: '執行電路設計、韌體開發與壓力測試任務。', color: 'from-emerald-400 to-emerald-600' }
  }
  return meta[roleName] || { icon: '👤', desc: '標準系統成員權限。', color: 'from-gray-500 to-gray-700' }
}

const fetchData = async () => {
  loading.value = true
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    
    const [userRes, deptRes] = await Promise.all([
      axios.get('/api/users', config),
      axios.get('/api/departments', config)
    ])
    
    users.value = Array.isArray(userRes.data) ? userRes.data : (userRes.data.value || userRes.data.data || [])
    departments.value = Array.isArray(deptRes.data) ? deptRes.data : (deptRes.data.value || deptRes.data.data || [])

    try {
      const [roleRes, detailedRoleRes, permRes] = await Promise.all([
        axios.get('/api/roles', config),
        axios.get('/api/roles-detailed', config),
        axios.get('/api/permissions', config)
      ])
      
      allRoles.value = Array.isArray(roleRes.data) ? roleRes.data : (roleRes.data.value || roleRes.data.data || [])
      const roleData = detailedRoleRes.data
      rolesDetailed.value = Array.isArray(roleData) ? roleData : (roleData.value || roleData.data || [])
      availablePermissions.value = Array.isArray(permRes.data) ? permRes.data : (permRes.data.value || permRes.data.data || [])
    } catch (metaErr) {
      console.warn('Metadata partial failure', metaErr)
    }

  } catch (err) {
    console.error('Core data failed', err)
  } finally {
    loading.value = false
  }
}

const openRoleEditModal = (role: any = null) => {
  if (role) {
    selectedRole.value = role
    roleForm.value = {
      name: role.name,
      department: role.department || '',
      description: role.description || '',
      icon: role.icon || '',
      color: role.color || '',
      permissions: [...role.permissions]
    }
  } else {
    selectedRole.value = null
    roleForm.value = { name: '', department: '', description: '', icon: '', color: '', permissions: [] }
  }
  showRoleEditModal.value = true
}

const saveRole = async () => {
  try {
    if (selectedRole.value) {
      await axios.put(`/api/roles/${selectedRole.value.id}`, roleForm.value, {
        headers: { Authorization: `Bearer ${auth.token}` }
      })
    } else {
      await axios.post('/api/roles', roleForm.value, {
        headers: { Authorization: `Bearer ${auth.token}` }
      })
    }
    showRoleEditModal.value = false
    fetchData()
  } catch (err) { alert('角色儲存失敗') }
}

const deleteRole = async (id: number) => {
  if (!confirm('確定要刪除此角色權限組嗎？')) return
  try {
    await axios.delete(`/api/roles/${id}`, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
  } catch (err) { alert('刪除失敗') }
}

const openCreateModal = () => {
  isEditing.value = false
  selectedUser.value = null
  userForm.value = { name: '', email: '', password: '', department: '', roles: [] }
  showEditModal.value = true
}

const openEditModal = (user: any) => {
  isEditing.value = true
  selectedUser.value = user
  userForm.value = {
    name: user.name,
    email: user.email,
    password: '',
    department: user.department || '',
    roles: [...user.roles]
  }
  showEditModal.value = true
}

const saveUser = async () => {
  try {
    const config = { headers: { Authorization: `Bearer ${auth.token}` } }
    if (isEditing.value && selectedUser.value) {
      const payload: any = { ...userForm.value }
      if (!payload.password) delete payload.password
      await axios.put(`/api/users/${selectedUser.value.id}`, payload, config)
    } else {
      await axios.post('/api/users', userForm.value, config)
    }
    showEditModal.value = false
    fetchData()
  } catch (err) { alert('儲存失敗') }
}

const deleteUser = async (id: number) => {
  if (!confirm('確定要永久停權此成員嗎？')) return
  try {
    await axios.delete(`/api/users/${id}`, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
  } catch (err) { alert('刪除失敗') }
}

const handleAdjustPoints = async (userId: number) => {
  if (pointAdjustment.value.amount === 0) return
  try {
    await axios.post(`/api/users/${userId}/adjust-points`, pointAdjustment.value, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    fetchData()
    pointAdjustment.value = { amount: 0, reason: '' }
  } catch (err) { alert('調整失敗') }
}

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-[url('https://images.unsplash.com/photo-1614850523296-d8c1af93d400?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-fixed overflow-x-hidden">
    <div class="min-h-screen backdrop-blur-sm bg-black/10 overflow-x-hidden">
      
      <Navbar />

      <!-- Main Header -->
      <header class="p-4 md:p-8 pb-0 max-w-[1600px] mx-auto">
        <div class="flex flex-col md:flex-row md:justify-between md:items-end space-y-4 md:space-y-0">
          <div>
            <h1 class="text-3xl md:text-5xl font-black text-white mb-2 drop-shadow-lg tracking-tight">成員權限矩陣</h1>
            <p class="text-blue-100/60 font-medium tracking-wide text-xs md:text-base uppercase tracking-[0.2em]">Matrix Member & Access Management</p>
          </div>
          <div class="flex space-x-2 md:space-x-4">
             <button @click="showRoleSettingsModal = true" class="flex-1 md:flex-none bg-white/10 hover:bg-white/20 text-white font-black py-3 md:py-4 px-6 md:px-8 rounded-2xl border border-white/10 transition-all text-[10px] md:text-xs uppercase tracking-widest">⚙️ 職能群組設定</button>
             <button @click="openCreateModal" class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-500 text-white font-black py-3 md:py-4 px-6 md:px-10 rounded-2xl shadow-xl shadow-blue-500/30 transition-all transform active:scale-95 text-[10px] md:text-xs uppercase tracking-widest">+ 註冊新成員</button>
          </div>
        </div>
      </header>

      <!-- Users Grid -->
      <main class="p-4 md:p-8 max-w-[1600px] mx-auto">
        <div v-if="loading" class="flex justify-center py-40">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-white/20 border-t-blue-500"></div>
        </div>
        
        <div v-else>
          <!-- Desktop Table View -->
          <div class="hidden lg:block bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="text-[10px] font-black uppercase tracking-[0.25em] text-white/30 border-b border-white/5">
                    <th class="px-10 py-6">成員資訊</th>
                    <th class="px-10 py-6">事業組</th>
                    <th class="px-10 py-6">點數餘額</th>
                    <th class="px-10 py-6">職能角色</th>
                    <th class="px-10 py-6 text-right">操作</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-white">
                  <tr v-for="user in users" :key="user.id" class="hover:bg-white/5 transition-all group">
                    <td class="px-10 py-8">
                      <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center font-black text-xl border border-white/20">
                          {{ user.name.substring(0,1).toUpperCase() }}
                        </div>
                        <div>
                          <p class="font-black text-white text-lg">{{ user.name }}</p>
                          <p class="text-white/30 text-[11px]">{{ user.email }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-10 py-8">
                      <span class="text-[10px] font-black uppercase tracking-widest text-blue-300 bg-blue-500/10 px-3 py-1 rounded-lg border border-blue-500/20">
                        {{ user.department || '未分配' }}
                      </span>
                    </td>
                    <td class="px-10 py-8">
                      <div class="flex items-center space-x-2 text-yellow-400 font-black">
                        <span class="text-lg">✦</span><span class="text-xl">{{ user.points }}</span>
                      </div>
                    </td>
                    <td class="px-10 py-8">
                      <div class="flex flex-wrap gap-1.5 max-w-[300px]">
                        <span v-for="role in user.roles" :key="role" class="text-[9px] font-black bg-white/10 text-white/60 px-2 py-0.5 rounded border border-white/5">
                          {{ role }}
                        </span>
                      </div>
                    </td>
                    <td class="px-10 py-8 text-right">
                      <div class="flex justify-end items-center space-x-3">
                        <button @click="openEditModal(user)" class="bg-white/10 hover:bg-white/20 text-white font-black text-[10px] px-6 py-2.5 rounded-xl transition-all uppercase tracking-widest border border-white/10">管理</button>
                        <button @click="deleteUser(user.id)" class="p-2.5 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white border border-red-500/20 rounded-xl transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"></path></svg></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Mobile Card View -->
          <div class="lg:hidden space-y-4">
            <div v-for="user in users" :key="user.id" class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2rem] p-5 shadow-xl relative overflow-hidden group">
              <div class="flex items-center space-x-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center font-black text-xl border border-white/20 text-white">
                  {{ user.name.substring(0,1).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <h4 class="text-white font-black text-lg truncate">{{ user.name }}</h4>
                  <p class="text-white/30 text-[11px] truncate">{{ user.email }}</p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-1">事業組</p>
                  <span class="text-[10px] font-black text-blue-300 bg-blue-500/10 px-2 py-0.5 rounded border border-blue-500/10 block w-fit">
                    {{ user.department || '未分配' }}
                  </span>
                </div>
                <div class="text-right">
                  <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-1">點數餘額</p>
                  <div class="text-yellow-400 font-black flex items-center justify-end space-x-1">
                    <span>✦</span><span class="text-lg leading-none">{{ user.points }}</span>
                  </div>
                </div>
              </div>

              <div class="mb-5">
                <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-2">職能角色</p>
                <div class="flex flex-wrap gap-1.5">
                  <span v-for="role in user.roles" :key="role" class="text-[9px] font-black bg-white/5 text-white/40 px-2 py-0.5 rounded border border-white/5">
                    {{ role }}
                  </span>
                </div>
              </div>

              <div class="flex items-center justify-between pt-4 border-t border-white/5">
                <div class="flex bg-black/40 rounded-xl overflow-hidden border border-white/10 p-0.5">
                  <button @click="pointAdjustment = {amount: -50, reason: '管理員調整'}; handleAdjustPoints(user.id)" class="px-3 py-1.5 hover:bg-red-500 text-white transition-all font-black text-[10px] rounded-lg">-50</button>
                  <button @click="pointAdjustment = {amount: 50, reason: '管理員調整'}; handleAdjustPoints(user.id)" class="px-3 py-1.5 hover:bg-green-600 text-white transition-all font-black text-[10px] rounded-lg">+50</button>
                </div>
                <div class="flex space-x-2">
                  <button @click="openEditModal(user)" class="bg-white/10 hover:bg-white/20 text-white font-black text-[10px] px-4 py-2 rounded-xl transition-all border border-white/10 uppercase tracking-widest">管理</button>
                  <button @click="deleteUser(user.id)" class="p-2 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"></path></svg></button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- Edit User Modal -->
      <Transition name="modal">
        <div v-if="showEditModal" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showEditModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-2xl rounded-t-[3rem] sm:rounded-[3.5rem] p-8 md:p-12 shadow-2xl max-h-[95vh] overflow-y-auto custom-scrollbar">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-1 tracking-tight">{{ isEditing ? '修改成員權限' : '新增成員' }}</h2>
            <p class="text-white/30 text-[11px] md:text-sm mb-8 md:mb-10">{{ isEditing ? '正在編輯 ' + selectedUser.name : '請輸入新成員的基礎登入資訊' }}</p>
            
            <div class="space-y-6 md:space-y-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">姓名</label><input v-model="userForm.name" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base"></div>
                <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">電子郵件</label><input v-model="userForm.email" type="email" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base"></div>
                <div v-if="!isEditing"><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">設定密碼</label><input v-model="userForm.password" type="password" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm md:text-base"></div>
                <div>
                  <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">所屬部門</label>
                  <div class="relative">
                    <div @click="showDeptDropdown = !showDeptDropdown" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white cursor-pointer flex justify-between items-center hover:border-blue-500/50 transition-all">
                      <span :class="userForm.department ? 'text-white font-bold' : 'text-white/20'" class="text-sm md:text-base">{{ userForm.department || '選擇事業組' }}</span>
                      <svg class="w-4 h-4 text-white/20 transition-transform" :class="{'rotate-180': showDeptDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2"></path></svg>
                    </div>
                    <div v-if="showDeptDropdown" class="absolute z-[210] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-40 overflow-y-auto custom-scrollbar">
                      <div @click="selectDepartment('')" class="px-5 py-3 text-white/40 hover:bg-white/5 cursor-pointer text-xs italic border-b border-white/5">未分配</div>
                      <div v-for="dept in departments" :key="dept.id" @click="selectDepartment(dept.name)" class="px-5 py-3 text-white font-bold hover:bg-blue-600 cursor-pointer border-b border-white/5 last:border-0 text-sm">{{ dept.name }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-4 md:mb-5 tracking-widest ml-1 text-center">職能角色分配</label>
                <div class="grid grid-cols-1 gap-2.5 md:gap-3">
                  <label v-for="role in allRoles" :key="role" class="relative group cursor-pointer block">
                    <input type="checkbox" :value="role" v-model="userForm.roles" class="absolute opacity-0 w-0 h-0 peer">
                    <div class="flex items-center p-3 md:p-5 bg-white/5 border border-white/15 rounded-2xl transition-all duration-300 group-hover:bg-white/10 peer-checked:bg-gradient-to-r peer-checked:border-blue-400 peer-checked:shadow-[0_0_25px_rgba(37,99,235,0.1)]" :class="userForm.roles.includes(role) ? getRoleMetadata(role).color : ''">
                      <div class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-black/30 flex items-center justify-center text-xl md:text-3xl mr-3 md:mr-5 border border-white/5">{{ getRoleMetadata(role).icon }}</div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between"><h4 class="font-black text-sm md:text-base uppercase tracking-tight text-white">{{ role }}</h4><div v-if="userForm.roles.includes(role)" class="w-5 h-5 bg-white text-blue-600 rounded-full flex items-center justify-center shadow-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="4"></path></svg></div></div>
                        <p class="text-[9px] md:text-[11px] leading-relaxed mt-0.5 font-medium line-clamp-1 md:line-clamp-none" :class="userForm.roles.includes(role) ? 'text-white' : 'text-white/60'">{{ getRoleMetadata(role).desc }}</p>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <div v-if="isEditing" class="pt-6 md:pt-8 border-t border-white/10">
                <h4 class="text-yellow-500 font-black text-[10px] uppercase tracking-widest mb-4 text-center">點數手動控制</h4>
                <div class="flex flex-col sm:flex-row items-center gap-3 bg-white/5 p-4 md:p-6 rounded-[2rem] md:rounded-[2.5rem] border border-white/5">
                  <input v-model="pointAdjustment.amount" type="number" placeholder="數值" class="w-full sm:w-24 bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-yellow-400 text-center font-black outline-none focus:border-yellow-500 transition-all text-sm">
                  <input v-model="pointAdjustment.reason" type="text" placeholder="輸入調整原因..." class="flex-1 w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-bold outline-none focus:border-blue-500 transition-all text-sm">
                  <button @click="handleAdjustPoints(selectedUser.id)" class="w-full sm:w-auto bg-yellow-600 hover:bg-yellow-500 text-black font-black px-8 py-3 rounded-xl transition-all shadow-lg text-[10px] uppercase tracking-widest">執行更新</button>
                </div>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-10 md:mt-12 pt-6 md:pt-8 border-t border-white/5">
              <button @click="showEditModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs">取消</button>
              <button @click="saveUser" class="order-1 sm:order-2 flex-2 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest text-xs md:text-sm">{{ isEditing ? '儲存變更' : '建立帳號' }}</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Role Settings Modal -->
      <Transition name="modal">
        <div v-if="showRoleSettingsModal" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showRoleSettingsModal = false"></div>
          <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-5xl rounded-t-[3rem] sm:rounded-[3.5rem] p-6 md:p-10 shadow-2xl max-h-[95vh] flex flex-col">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 md:mb-10 gap-6 shrink-0">
              <div class="flex items-center space-x-4 md:space-x-6">
                <div class="w-14 h-14 md:w-20 md:h-20 bg-gradient-to-tr from-purple-600/30 to-blue-600/30 rounded-2xl md:rounded-[2.2rem] border border-white/10 flex items-center justify-center shadow-xl">
                  <svg class="w-8 h-8 md:w-10 md:h-10 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="2"></path></svg>
                </div>
                <div>
                  <h2 class="text-2xl md:text-4xl font-black text-white tracking-tighter">權限群組設定</h2>
                  <p class="text-white/40 text-[9px] md:text-[10px] mt-1 uppercase tracking-widest font-black">Security Matrix</p>
                </div>
              </div>
              <button @click="openRoleEditModal()" class="bg-blue-600 hover:bg-blue-500 text-white font-black px-6 md:px-10 py-3 md:py-4 rounded-xl md:rounded-2xl transition-all shadow-xl active:scale-95 text-xs uppercase tracking-widest">+ 建立新群組</button>
            </div>

            <div class="flex-1 overflow-y-auto pr-2 md:pr-4 custom-scrollbar pb-10">
              <div v-if="rolesDetailed.length === 0" class="text-center py-20 bg-white/5 rounded-[3rem] border border-dashed border-white/10">
                <p class="text-white/20 font-bold uppercase tracking-widest text-xs italic">尚無定義任何權限群組</p>
              </div>
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <div v-for="role in rolesDetailed" :key="role.id" class="bg-white/5 border border-white/10 p-6 md:p-8 rounded-[2.5rem] hover:bg-white/10 transition-all group relative overflow-hidden">
                  <div class="relative z-10 flex justify-between items-start mb-6">
                    <div class="flex items-center space-x-4">
                      <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-black/50 flex items-center justify-center text-2xl md:text-3xl border border-white/5">{{ getRoleMetadata(role.name).icon }}</div>
                      <div>
                        <h4 class="text-white font-black text-lg md:text-xl tracking-tight">{{ role.name }}</h4>
                        <span class="text-[8px] md:text-[9px] font-black uppercase tracking-widest text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded mt-1 inline-block border border-blue-500/20">{{ role.department || '全域通用' }}</span>
                      </div>
                    </div>
                    <div class="flex space-x-1.5">
                      <button @click="openRoleEditModal(role)" class="p-2.5 bg-white/5 hover:bg-blue-600 text-blue-400 hover:text-white rounded-xl transition-all border border-white/5"><svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"></path></svg></button>
                      <button v-if="role.name !== 'SuperAdmin'" @click="deleteRole(role.id)" class="p-2.5 bg-white/5 hover:bg-red-600 text-red-400 hover:text-white rounded-xl transition-all border border-white/5"><svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"></path></svg></button>
                    </div>
                  </div>
                  <!-- Show dynamic description -->
                  <p class="text-white/40 text-[10px] md:text-xs leading-relaxed mb-6 line-clamp-2">{{ role.description || getRoleMetadata(role.name).desc }}</p>
                  
                  <div class="flex flex-wrap gap-2 pt-6 border-t border-white/5">
                    <span v-for="perm in role.permissions" :key="perm" class="text-[8px] md:text-[9px] font-black bg-black/40 text-white/50 px-2.5 py-1 rounded-lg border border-white/5 uppercase tracking-tighter">{{ perm }}</span>
                  </div>
                </div>
              </div>
            </div>
            <button @click="showRoleSettingsModal = false" class="w-full mt-4 py-6 text-white/20 font-black hover:text-white transition-all uppercase text-[9px] tracking-[0.5em] border-t border-white/5 shrink-0">關閉設定中心</button>
          </div>
        </div>
      </Transition>

      <!-- Individual Role Edit Modal -->
      <Transition name="modal">
        <div v-if="showRoleEditModal" class="fixed inset-0 z-[300] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showRoleEditModal = false"></div>
          <div class="relative z-10 bg-gray-900 border-t sm:border border-white/10 w-full max-w-2xl rounded-t-[3rem] sm:rounded-[3rem] p-8 md:p-12 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-6 sm:hidden"></div>
            <div class="flex items-center space-x-5 mb-10">
              <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-2xl md:text-3xl shadow-inner">{{ selectedRole ? '⚙️' : '✨' }}</div>
              <div>
                <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">{{ selectedRole ? '群組權限配置' : '定義新權限群組' }}</h2>
                <p class="text-white/30 text-[10px] font-bold uppercase tracking-widest mt-1">Matrix Role Settings</p>
              </div>
            </div>
            
            <div class="space-y-8 md:space-y-10">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 bg-white/5 p-6 md:p-8 rounded-[2rem] md:rounded-[2.5rem] border border-white/5">
                <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">群組名稱</label><input v-model="roleForm.name" type="text" placeholder="如: Project-Lead" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white font-bold outline-none focus:border-purple-500 transition-all text-sm"></div>
                <div>
                  <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">綁定事業組</label>
                  <div class="relative">
                    <div @click="showRoleDeptDropdown = !showRoleDeptDropdown" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 md:py-4 text-white cursor-pointer flex justify-between items-center hover:border-purple-500/50 transition-all">
                      <span :class="roleForm.department ? 'text-white font-bold' : 'text-white/20'" class="text-sm">{{ roleForm.department || '不限部門 (全域)' }}</span>
                      <svg class="w-4 h-4 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2"></path></svg>
                    </div>
                    <div v-if="showRoleDeptDropdown" class="absolute z-[310] w-full mt-2 bg-gray-800 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-3xl max-h-40 overflow-y-auto custom-scrollbar">
                      <div @click="selectRoleDepartment('')" class="px-5 py-3 text-white/40 hover:bg-white/5 cursor-pointer text-xs italic border-b border-white/5">不限部門</div>
                      <div v-for="dept in departments" :key="dept.id" @click="selectRoleDepartment(dept.name)" class="px-5 py-3 text-white font-bold hover:bg-purple-600 cursor-pointer border-b border-white/5 last:border-0 text-sm">{{ dept.name }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Added Description Field -->
              <div>
                <label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">權限群組敘述</label>
                <textarea v-model="roleForm.description" placeholder="請輸入此權限群組的職責說明..." class="w-full bg-white/5 border border-white/10 rounded-[1.5rem] px-5 py-4 text-white font-medium outline-none focus:border-purple-500 transition-all h-24 resize-none text-sm"></textarea>
              </div>

              <!-- Added Icon and Color Fields (Optional Visuals) -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                 <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">代表圖標 (Emoji)</label><input v-model="roleForm.icon" type="text" placeholder="如: 🛡️" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white font-bold outline-none focus:border-purple-500 transition-all text-sm"></div>
                 <div><label class="block text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest ml-1">漸層顏色 (Tailwind Classes)</label><input v-model="roleForm.color" type="text" placeholder="from-blue-600 to-indigo-700" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white font-bold outline-none focus:border-purple-500 transition-all text-sm"></div>
              </div>

              <div>
                <label class="block text-[10px] font-black text-white/40 uppercase mb-5 tracking-widest ml-1 text-center">功能權限清單 (Matrix)</label>
                <div class="grid grid-cols-1 gap-2.5 max-h-60 md:max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                  <label v-for="perm in availablePermissions" :key="perm" class="relative group block cursor-pointer">
                    <input type="checkbox" :value="perm" v-model="roleForm.permissions" class="absolute opacity-0 w-0 h-0 peer">
                    <div class="flex items-center p-4 bg-white/5 border border-white/10 rounded-2xl transition-all duration-300 group-hover:bg-white/10 peer-checked:bg-purple-600/30 peer-checked:border-purple-500/40">
                      <div class="w-7 h-7 rounded-lg bg-black/40 flex items-center justify-center mr-3 border border-white/5"><div class="w-2 h-2 rounded-full transition-all border-2 border-white/20" :class="roleForm.permissions.includes(perm) ? 'bg-white border-white scale-125' : 'bg-transparent'"></div></div>
                      <span class="text-[10px] md:text-xs font-black uppercase tracking-widest transition-colors" :class="roleForm.permissions.includes(perm) ? 'text-white' : 'text-white/40'">{{ perm }}</span>
                      
                      <div class="ml-auto flex space-x-1">
                        <span v-if="perm.includes('task') && !perm.includes('pool')" class="text-[7px] md:text-[8px] font-black bg-blue-500/20 text-blue-300 px-2 py-0.5 rounded-full uppercase border border-blue-500/10">Task</span>
                        <span v-if="perm.includes('pool')" class="text-[7px] md:text-[8px] font-black bg-orange-500/20 text-orange-300 px-2 py-0.5 rounded-full uppercase border border-orange-500/10">Pool</span>
                        <span v-if="perm.includes('project')" class="text-[7px] md:text-[8px] font-black bg-green-500/20 text-green-300 px-2 py-0.5 rounded-full uppercase border border-green-500/10">Project</span>
                        <span v-if="perm.includes('manage') || perm.includes('adjust')" class="text-[7px] md:text-[8px] font-black bg-purple-500/20 text-purple-300 px-2 py-0.5 rounded-full uppercase border border-purple-500/10">Admin</span>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-10 md:mt-12 pt-6 md:pt-8 border-t border-white/5">
              <button @click="showRoleEditModal = false" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs tracking-widest">取消</button>
              <button @click="saveRole" class="order-1 sm:order-2 flex-2 bg-purple-600 hover:bg-purple-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg active:scale-95 text-xs uppercase tracking-widest">確認配置更新</button>
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
