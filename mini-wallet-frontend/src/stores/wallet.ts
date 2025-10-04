import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { transactionService, type User, type Transaction } from '@/services/api'
import { initializePusher, subscribeToUserChannel, disconnectPusher } from '@/services/pusher'

export const useWalletStore = defineStore('wallet', () => {
  // State
  const user = ref<User | null>(null)
  const transactions = ref<Transaction[]>([])
  const balance = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const isAuthenticated = computed(() => !!user.value)
  const recentTransactions = computed(() => transactions.value.slice(0, 10))

  // Actions
  const setUser = (userData: User) => {
    user.value = userData
    balance.value = userData.balance
  }

  const setTransactions = (transactionData: Transaction[]) => {
    transactions.value = transactionData
  }

  const addTransaction = (transaction: Transaction) => {
    transactions.value.unshift(transaction)
  }

  const updateBalance = (newBalance: number) => {
    balance.value = newBalance
    if (user.value) {
      user.value.balance = newBalance
    }
  }

  const setLoading = (isLoading: boolean) => {
    loading.value = isLoading
  }

  const setError = (errorMessage: string | null) => {
    error.value = errorMessage
  }

  const fetchTransactions = async () => {
    try {
      setLoading(true)
      setError(null)
      const response = await transactionService.getTransactions()
      setTransactions(response.transactions.data)
      updateBalance(response.balance)
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch transactions')
      throw err
    } finally {
      setLoading(false)
    }
  }

  const createTransaction = async (receiverId: number, amount: number) => {
    try {
      setLoading(true)
      setError(null)
      const response = await transactionService.createTransaction({
        receiver_id: receiverId,
        amount: amount
      })
      
      addTransaction(response.transaction)
      updateBalance(response.new_balance)
      
      return response
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to create transaction')
      throw err
    } finally {
      setLoading(false)
    }
  }

  const initializeRealTimeUpdates = () => {
    if (!user.value) return

    const pusher = initializePusher(user.value.id)
    subscribeToUserChannel(user.value.id, (data: any) => {
      if (data.type === 'transaction_completed') {
        addTransaction(data.transaction)
        // Update balance if needed
        if (data.transaction.sender_id === user.value?.id) {
          // User sent money, balance should be updated
          fetchTransactions()
        } else if (data.transaction.receiver_id === user.value?.id) {
          // User received money, balance should be updated
          fetchTransactions()
        }
      }
    })
  }

  const logout = () => {
    user.value = null
    transactions.value = []
    balance.value = 0
    error.value = null
    disconnectPusher()
  }

  return {
    // State
    user,
    transactions,
    balance,
    loading,
    error,
    // Getters
    isAuthenticated,
    recentTransactions,
    // Actions
    setUser,
    setTransactions,
    addTransaction,
    updateBalance,
    setLoading,
    setError,
    fetchTransactions,
    createTransaction,
    initializeRealTimeUpdates,
    logout
  }
})
