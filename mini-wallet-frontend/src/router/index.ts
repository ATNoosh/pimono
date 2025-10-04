import { createRouter, createWebHistory } from 'vue-router'
import { useWalletStore } from '@/stores/wallet'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/AuthView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/wallet',
      name: 'wallet',
      component: () => import('../views/WalletView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
    },
  ],
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const walletStore = useWalletStore()
  
  if (to.meta.requiresAuth && !walletStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.requiresGuest && walletStore.isAuthenticated) {
    next('/wallet')
  } else {
    next()
  }
})

export default router
