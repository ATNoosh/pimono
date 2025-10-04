<template>
  <div class="wallet-view">
    <div class="max-w-6xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Mini Wallet</h1>
        <p class="text-gray-600">Welcome back, {{ walletStore.user?.name }}!</p>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Transfer Form -->
        <div class="order-2 lg:order-1">
          <TransferForm />
        </div>

        <!-- Transaction History -->
        <div class="order-1 lg:order-2">
          <TransactionHistory />
        </div>
      </div>

      <!-- Real-time Status -->
      <div v-if="isConnected" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-md">
        <div class="flex items-center">
          <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
          <span class="text-sm text-green-700">Connected to real-time updates</span>
        </div>
      </div>

      <div v-else class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
        <div class="flex items-center">
          <div class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></div>
          <span class="text-sm text-yellow-700">Connecting to real-time updates...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import TransferForm from '@/components/TransferForm.vue'
import TransactionHistory from '@/components/TransactionHistory.vue'

const walletStore = useWalletStore()
const isConnected = ref(false)

onMounted(async () => {
  // Initialize real-time updates
  if (walletStore.user) {
    walletStore.initializeRealTimeUpdates()
    isConnected.value = true
  }
})

onUnmounted(() => {
  // Cleanup real-time connections
  walletStore.logout()
})
</script>

<style scoped>
.wallet-view {
  @apply min-h-screen bg-gray-50;
}
</style>
