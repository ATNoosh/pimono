<template>
  <div class="auth-view">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-16">
      <div class="w-full max-w-3xl px-6 lg:px-8 space-y-10">
        <div class="text-center">
          <div class="inline-flex items-center justify-center gap-2 mb-4">
            <span class="inline-block h-10 w-10 rounded-lg bg-blue-600"></span>
            <span class="text-2xl font-bold text-gray-900">Mini Wallet</span>
          </div>
          <p class="text-sm text-gray-600">Secure money transfers with real-time updates</p>
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
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>
