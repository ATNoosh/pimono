import Pusher from 'pusher-js'

const PUSHER_KEY = '457df54d0b56682441fc'
const PUSHER_CLUSTER = 'ap2'
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api').replace(/\/+$/, '')
const AUTH_ENDPOINT = `${API_BASE_URL}/broadcasting/auth`

let pusher: Pusher | null = null

export const initializePusher = (userId: number) => {
  if (pusher) {
    pusher.disconnect()
  }

  pusher = new Pusher(PUSHER_KEY, {
    cluster: PUSHER_CLUSTER,
    forceTLS: true,
    enableStats: false,
    authEndpoint: AUTH_ENDPOINT,
    auth: {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    },
  })

  // Debug connection states
  pusher.connection.bind('state_change', (states: any) => {
    console.debug('[Pusher] state_change', states)
  })
  pusher.connection.bind('error', (err: any) => {
    console.error('[Pusher] error', err)
  })

  return pusher
}

export const subscribeToUserChannel = (userId: number, callback: (data: any) => void) => {
  if (!pusher) {
    console.error('Pusher not initialized')
    return null
  }

  const channel = pusher.subscribe(`private-user.${userId}`)
  
  channel.bind('transaction.completed', callback)
  
  return channel
}

export const unsubscribeFromUserChannel = (userId: number) => {
  if (!pusher) return

  pusher.unsubscribe(`private-user.${userId}`)
}

export const disconnectPusher = () => {
  if (pusher) {
    pusher.disconnect()
    pusher = null
  }
}

export default pusher
