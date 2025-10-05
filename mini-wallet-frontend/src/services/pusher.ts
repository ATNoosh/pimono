import Pusher from 'pusher-js'

const PUSHER_KEY = import.meta.env.VITE_PUSHER_APP_KEY || 'your_app_key'
const PUSHER_CLUSTER = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1'

let pusher: Pusher | null = null

export const initializePusher = (userId: number) => {
  if (pusher) {
    pusher.disconnect()
  }

  pusher = new Pusher(PUSHER_KEY, {
    cluster: PUSHER_CLUSTER,
    authEndpoint: '/api/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    },
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
