<template>
  <div class="transfer-form">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Send Money</h2>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div>
        <label for="receiver_id" class="block text-sm font-medium text-gray-700 mb-2">
          Receiver ID
        </label>
        <input
          id="receiver_id"
          v-model="form.receiver_id"
          type="number"
          min="1"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.receiver_id }"
          placeholder="Enter receiver's user ID"
        />
        <p v-if="errors.receiver_id" class="mt-1 text-sm text-red-600">
          {{ errors.receiver_id }}
        </p>
      </div>

      <div>
        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
          Amount
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
          <input
            id="amount"
            v-model="form.amount"
            type="number"
            step="0.01"
            min="0.01"
            max="999999.99"
            required
            class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.amount }"
            placeholder="0.00"
          />
        </div>
        <p v-if="errors.amount" class="mt-1 text-sm text-red-600">
          {{ errors.amount }}
        </p>
        <p class="mt-1 text-sm text-gray-500">
          Commission fee (1.5%): ${{ commissionFee.toFixed(2) }}
        </p>
        <p class="mt-1 text-sm text-gray-500">
          Total amount: ${{ totalAmount.toFixed(2) }}
        </p>
      </div>

      <div v-if="walletStore.error" class="p-4 bg-red-50 border border-red-200 rounded-md">
        <p class="text-sm text-red-600">{{ walletStore.error }}</p>
      </div>

      <div v-if="successMessage" class="p-4 bg-green-50 border border-green-200 rounded-md">
        <p class="text-sm text-green-600">{{ successMessage }}</p>
      </div>

      <button
        type="submit"
        :disabled="walletStore.loading || !isFormValid"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        <span v-if="walletStore.loading" class="flex items-center justify-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Sending...
        </span>
        <span v-else>Send Money</span>
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { useWalletStore } from '@/stores/wallet'

const walletStore = useWalletStore()

const form = reactive({
  receiver_id: '',
  amount: ''
})

const errors = ref<Record<string, string>>({})
const successMessage = ref('')

const commissionFee = computed(() => {
  const amount = parseFloat(form.amount) || 0
  return amount * 0.015
})

const totalAmount = computed(() => {
  const amount = parseFloat(form.amount) || 0
  return amount + commissionFee.value
})

const isFormValid = computed(() => {
  const receiverId = parseInt(form.receiver_id)
  const amount = parseFloat(form.amount)
  
  return receiverId > 0 && 
         amount > 0 && 
         amount <= 999999.99 &&
         receiverId !== walletStore.user?.id
})

const validateForm = () => {
  errors.value = {}
  
  const receiverId = parseInt(form.receiver_id)
  const amount = parseFloat(form.amount)
  
  if (!receiverId || receiverId <= 0) {
    errors.value.receiver_id = 'Please enter a valid receiver ID'
  }
  
  if (receiverId === walletStore.user?.id) {
    errors.value.receiver_id = 'Cannot send money to yourself'
  }
  
  if (!amount || amount <= 0) {
    errors.value.amount = 'Please enter a valid amount'
  } else if (amount > 999999.99) {
    errors.value.amount = 'Amount cannot exceed $999,999.99'
  }
  
  if (totalAmount.value > walletStore.balance) {
    errors.value.amount = 'Insufficient balance'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  try {
    successMessage.value = ''
    await walletStore.createTransaction(
      parseInt(form.receiver_id),
      parseFloat(form.amount)
    )
    
    successMessage.value = 'Transfer completed successfully!'
    
    // Reset form
    form.receiver_id = ''
    form.amount = ''
    
    // Clear success message after 3 seconds
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
    
  } catch (error) {
    console.error('Transfer failed:', error)
  }
}
</script>

<style scoped>
.transfer-form {
  @apply bg-white p-6 rounded-lg shadow-md;
}
</style>
