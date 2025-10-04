<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWalletStore } from '@/stores/wallet'

const router = useRouter()
const walletStore = useWalletStore()

onMounted(() => {
  if (walletStore.isAuthenticated) {
    router.push('/wallet')
  } else {
    router.push('/login')
  }
})
</script>

<template>
  <div class="home-view">
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Mini Wallet</h1>
        <p class="text-lg text-gray-600 mb-8">Secure money transfers with real-time updates</p>
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
      </div>
    </div>
  </div>
</template>
