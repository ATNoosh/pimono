<template>
  <div class="transaction-history">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Transaction History</h2>
      <div class="text-right">
        <p class="text-sm text-gray-600">Current Balance</p>
        <p class="text-3xl font-bold text-green-600">${{ balance.toFixed(2) }}</p>
      </div>
    </div>

    <div v-if="walletStore.loading" class="flex justify-center items-center py-8">
      <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <div v-else-if="transactions.length === 0" class="text-center py-8">
      <div class="text-gray-400 mb-4">
        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
      </div>
      <p class="text-gray-500">No transactions yet</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="transaction in transactions"
        :key="transaction.id"
        class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center"
                :class="getTransactionIconClass(transaction)"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path v-if="isSentTransaction(transaction)" fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                  <path v-else fill-rule="evenodd" d="M10 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900">
                  {{ isSentTransaction(transaction) ? 'Sent to' : 'Received from' }}
                  {{ isSentTransaction(transaction) ? transaction.receiver?.name : transaction.sender?.name }}
                </p>
                <p class="text-sm text-gray-500">
                  {{ formatDate(transaction.created_at) }}
                </p>
              </div>
            </div>
          </div>
          <div class="text-right">
            <p
              class="text-lg font-semibold"
              :class="isSentTransaction(transaction) ? 'text-red-600' : 'text-green-600'"
            >
              {{ isSentTransaction(transaction) ? '-' : '+' }}${{ transaction.amount.toFixed(2) }}
            </p>
            <p v-if="isSentTransaction(transaction)" class="text-sm text-gray-500">
              Fee: ${{ transaction.commission_fee.toFixed(2) }}
            </p>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="getStatusClass(transaction.status)"
            >
              {{ transaction.status }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="transactions.length > 0" class="mt-6 text-center">
      <button
        @click="loadMore"
        :disabled="walletStore.loading"
        class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Load More
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import type { Transaction } from '@/services/api'

const walletStore = useWalletStore()

const transactions = computed(() => walletStore.transactions)
const balance = computed(() => walletStore.balance)

const isSentTransaction = (transaction: Transaction) => {
  return transaction.sender_id === walletStore.user?.id
}

const getTransactionIconClass = (transaction: Transaction) => {
  return isSentTransaction(transaction)
    ? 'bg-red-100 text-red-600'
    : 'bg-green-100 text-green-600'
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadMore = () => {
  // Implement pagination if needed
  console.log('Load more transactions')
}

onMounted(() => {
  walletStore.fetchTransactions()
})
</script>

<style scoped>
.transaction-history {
  @apply bg-white p-6 rounded-lg shadow-md;
}
</style>
