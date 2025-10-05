import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle token expiration
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export interface User {
  id: number
  name: string
  email: string
  balance: number
}

export interface Transaction {
  id: number
  sender_id: number
  receiver_id: number
  amount: number
  commission_fee: number
  total_amount: number
  status: 'pending' | 'completed' | 'failed'
  description?: string
  created_at: string
  updated_at: string
  sender?: User
  receiver?: User
}

export interface TransactionResponse {
  balance: number
  transactions: {
    data: Transaction[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface TransferRequest {
  receiver_id: number
  amount: number
}

export const authService = {
  async login(email: string, password: string) {
    const response = await api.post('/login', { email, password })
    const token = response.data.token
    localStorage.setItem('auth_token', token)
    return response.data
  },

  async register(name: string, email: string, password: string, password_confirmation: string) {
    const response = await api.post('/register', { name, email, password, password_confirmation })
    const token = response.data.token
    localStorage.setItem('auth_token', token)
    return response.data
  },

  async logout() {
    localStorage.removeItem('auth_token')
  },

  async getUser() {
    const response = await api.get('/user')
    return response.data
  }
}

export const transactionService = {
  async getTransactions(): Promise<TransactionResponse> {
    const response = await api.get('/transactions')
    return response.data
  },

  async createTransaction(transferData: TransferRequest) {
    const response = await api.post('/transactions', transferData)
    return response.data
  }
}

export default api
