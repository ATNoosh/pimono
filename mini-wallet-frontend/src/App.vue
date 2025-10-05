<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { useWalletStore } from '@/stores/wallet'
import { authService } from '@/services/api'
import { onMounted } from 'vue'
import router from '@/router'

const walletStore = useWalletStore()

const onLogout = async () => {
  await walletStore.logout()
  router.push('/login')
}

onMounted(async () => {
  // Check if user is already logged in
  const token = localStorage.getItem('auth_token')
  if (token) {
    try {
      // First get user data
      const userResponse = await authService.getUser()
      walletStore.setUser(userResponse)
      
      // Then fetch transactions
      await walletStore.fetchTransactions()
    } catch (error) {
      // If failed, remove invalid token
      localStorage.removeItem('auth_token')
      console.error('Failed to initialize user:', error)
    }
  }
})
</script>

<template>
  <div class="app-root">
    <!-- Navigation -->
    <nav v-if="walletStore.isAuthenticated" class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <RouterLink to="/wallet" class="text-xl font-bold text-gray-900">
              <span class="inline-flex items-center gap-2">
                <span class="inline-block h-6 w-6 rounded-md bg-blue-600"></span>
                Mini Wallet
              </span>
            </RouterLink>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600 hidden sm:inline">
              Welcome, {{ walletStore.user?.name }}
            </span>
            <button
              @click="onLogout"
              class="text-sm text-gray-600 hover:text-gray-900 rounded-md px-3 py-1.5 border border-gray-200 hover:border-gray-300"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<style scoped>
header {
  line-height: 1.5;
  max-height: 100vh;
}

.logo {
  display: block;
  margin: 0 auto 2rem;
}

nav {
  width: 100%;
  font-size: 12px;
  text-align: center;
  margin-top: 2rem;
}

nav a.router-link-exact-active {
  color: var(--color-text);
}

nav a.router-link-exact-active:hover {
  background-color: transparent;
}

nav a {
  display: inline-block;
  padding: 0 1rem;
  border-left: 1px solid var(--color-border);
}

nav a:first-of-type {
  border: 0;
}

@media (min-width: 1024px) {
  header {
    display: flex;
    place-items: center;
    padding-right: calc(var(--section-gap) / 2);
  }

  .logo {
    margin: 0 2rem 0 0;
  }

  header .wrapper {
    display: flex;
    place-items: flex-start;
    flex-wrap: wrap;
  }

  nav {
    text-align: left;
    margin-left: -1rem;
    font-size: 1rem;

    padding: 1rem 0;
    margin-top: 1rem;
  }
}
</style>
