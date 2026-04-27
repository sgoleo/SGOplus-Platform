<script setup lang="ts">
defineProps<{
  show: boolean
  task: any
}>()

const emit = defineEmits(['close'])

const formatDate = (dateStr: string) => {
  if (!dateStr) return '無截止日期'
  return new Date(dateStr).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>

<template>
  <Transition name="modal">
    <div v-if="show && task" class="fixed inset-0 z-[500] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="emit('close')"></div>
      
      <!-- Modal Content -->
      <div class="relative z-10 bg-[#0a0f1e] border-t sm:border border-white/10 w-full max-w-2xl rounded-t-[3rem] sm:rounded-[3.5rem] p-8 md:p-12 shadow-[0_0_100px_rgba(0,0,0,0.5)] max-h-[90vh] overflow-y-auto custom-scrollbar text-white">
        <!-- Mobile Handle -->
        <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-8 sm:hidden"></div>
        
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
          <div>
            <div class="flex items-center space-x-3 mb-2">
              <span class="px-3 py-1 bg-blue-500/10 border border-blue-500/20 rounded-full text-[10px] font-black uppercase tracking-widest text-blue-400">
                {{ task.project?.name || '獨立任務' }}
              </span>
              <span v-if="task.department" class="text-[10px] font-black text-white/30 uppercase tracking-widest">
                {{ task.department }}
              </span>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-white tracking-tight leading-tight">{{ task.title }}</h2>
          </div>
          <div class="hidden sm:flex flex-col items-end">
             <div class="text-yellow-400 font-black flex items-center space-x-2">
               <span class="text-xl">✦</span>
               <span class="text-3xl drop-shadow-[0_0_15px_rgba(234,179,8,0.4)]">{{ task.reward_points }}</span>
             </div>
             <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mt-1">獎勵點數</p>
          </div>
        </div>

        <!-- Stats Grid (Mobile Reward) -->
        <div class="sm:hidden grid grid-cols-2 gap-4 mb-8 p-6 bg-white/5 rounded-3xl border border-white/5">
           <div>
             <p class="text-[9px] font-black text-white/20 uppercase mb-1">獎勵</p>
             <div class="text-yellow-400 font-black flex items-center space-x-1">
               <span class="text-sm">✦</span><span class="text-2xl">{{ task.reward_points }}</span>
             </div>
           </div>
           <div class="text-right">
             <p class="text-[9px] font-black text-white/20 uppercase mb-1">狀態</p>
             <span class="text-xs font-black uppercase tracking-widest text-blue-300">{{ task.status }}</span>
           </div>
        </div>

        <!-- Description Section -->
        <div class="mb-10">
          <h4 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-4 border-l-2 border-blue-500 pl-3">任務詳情描述</h4>
          <div class="bg-white/5 border border-white/5 rounded-3xl p-6 md:p-8">
            <p class="text-white/80 leading-relaxed text-sm md:text-base whitespace-pre-wrap font-medium">
              {{ task.details || '目前暫無詳細說明。' }}
            </p>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
          <div class="flex items-center space-x-4 p-5 bg-white/5 rounded-2xl border border-white/5">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2.5"></path></svg>
            </div>
            <div>
              <p class="text-[9px] font-black text-white/20 uppercase tracking-widest">截止日期</p>
              <p class="text-sm font-bold text-white/90">{{ formatDate(task.due_date) }}</p>
            </div>
          </div>
          
          <div class="flex items-center space-x-4 p-5 bg-white/5 rounded-2xl border border-white/5">
            <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2.5"></path></svg>
            </div>
            <div>
              <p class="text-[9px] font-black text-white/20 uppercase tracking-widest">參與人數</p>
              <p class="text-sm font-bold text-white/90">{{ task.assignees_count || 0 }} / {{ task.max_assignees || 1 }}</p>
            </div>
          </div>
        </div>

        <!-- Actions Slot -->
        <div class="flex flex-col sm:flex-row gap-4 pt-8 border-t border-white/10">
          <button @click="emit('close')" class="order-2 sm:order-1 flex-1 py-4 text-white/30 font-bold hover:text-white transition-all uppercase text-xs tracking-widest">關閉視窗</button>
          <div class="order-1 sm:order-2 flex-[2] flex gap-4">
            <slot name="actions"></slot>
          </div>
        </div>
      </div>
    </div>
  </Transition>
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
