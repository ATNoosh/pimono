<template>
  <div class="auth-view">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md w-full space-y-8">
        <div>
          <h1 class="text-center text-3xl font-extrabold text-gray-900">
            Mini Wallet
          </h1>
          <p class="mt-2 text-center text-sm text-gray-600">
            Secure money transfers with real-time updates
          </p>
        </div>

        <div v-if="isLogin" class="mt-8">
          <LoginForm @switch-to-register="isLogin = false" />
        </div>

        <div v-else class="mt-8">
          <RegisterForm @switch-to-login="isLogin = true" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import LoginForm from '@/components/LoginForm.vue'
import RegisterForm from '@/components/RegisterForm.vue'

const isLogin = ref(true)
</script>

<style scoped>
.auth-view {
  @apply min-h-screen bg-gray-50;
}
</style>
