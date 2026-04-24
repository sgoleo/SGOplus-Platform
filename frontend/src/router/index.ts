import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/Login.vue'),
      meta: { guest: true }
    },
    {
      path: '/',
      name: 'dashboard',
      component: () => import('../views/Dashboard.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/reviews',
      name: 'review-center',
      component: () => import('../views/ReviewCenter.vue'),
      meta: { requiresAuth: true, adminOnly: true }
    },
    {
      path: '/projects',
      name: 'project-management',
      component: () => import('../views/ProjectManagement.vue'),
      meta: { requiresAuth: true, adminOnly: true }
    },
    {
      path: '/tasks-management',
      name: 'task-management',
      component: () => import('../views/TaskManagement.vue'),
      meta: { requiresAuth: true, adminOnly: true }
    },
    {
      path: '/users',
      name: 'user-management',
      component: () => import('../views/UserManagement.vue'),
      meta: { requiresAuth: true, adminOnly: true }
    },
    {
      path: '/pool',
      name: 'task-pool',
      component: () => import('../views/TaskPool.vue'),
      meta: { requiresAuth: true }
    }
  ]
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next({ name: 'login' })
  } else if (to.meta.adminOnly && !auth.roles.includes('SuperAdmin')) {
    alert('權限不足')
    next({ name: 'dashboard' })
  } else if (to.meta.guest && auth.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
