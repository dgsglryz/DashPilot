<template>
  <Head title="Welcome to DashPilot" />
  <title>Welcome to DashPilot</title>
  
  <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
      <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>

    <div class="relative z-10 flex min-h-screen flex-col">
      <!-- Header -->
      <header class="border-b border-gray-700/50 bg-gray-900/50 backdrop-blur-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <span class="text-xl font-bold text-white">DashPilot</span>
            </div>
            
            <nav class="flex items-center gap-4">
              <Link
                v-if="$page.props.auth?.user"
                :href="route('dashboard')"
                class="rounded-lg px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
              >
                Dashboard
              </Link>
              <template v-else>
                <Link
                  :href="route('login')"
                  class="rounded-lg px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                >
                  Log in
                </Link>
                <Link
                  v-if="canRegister"
                  :href="route('register')"
                  class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                >
                  Sign up
                </Link>
              </template>
            </nav>
          </div>
        </div>
      </header>

      <!-- Hero Section -->
      <main class="flex flex-1 items-center">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20">
          <div class="grid gap-12 lg:grid-cols-2 lg:gap-16 items-center">
            <!-- Left: Content -->
            <div class="text-center lg:text-left">
              <h1 class="text-5xl font-bold tracking-tight text-white sm:text-6xl lg:text-7xl">
                Operations Dashboard
                <span class="block text-blue-400">for Web Agencies</span>
              </h1>
              <p class="mt-6 text-lg leading-8 text-gray-300 sm:text-xl">
                Monitor and manage 100+ WordPress and Shopify sites from a single, powerful dashboard. 
                Real-time alerts, performance tracking, and automated health checks.
              </p>
              
              <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:justify-center lg:justify-start">
                <Link
                  :href="route('register')"
                  class="rounded-lg bg-blue-600 px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                >
                  Get Started
                </Link>
                <Link
                  :href="route('login')"
                  class="rounded-lg border border-gray-600 bg-gray-800/50 px-6 py-3 text-base font-semibold text-white backdrop-blur-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                >
                  Sign In
                </Link>
              </div>

              <!-- Features Grid -->
              <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-3">
                <div class="text-center lg:text-left">
                  <div class="mx-auto lg:mx-0 mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-600/20">
                    <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <h3 class="text-lg font-semibold text-white">Real-time Monitoring</h3>
                  <p class="mt-2 text-sm text-gray-400">Track uptime, performance, and health across all your sites</p>
                </div>
                
                <div class="text-center lg:text-left">
                  <div class="mx-auto lg:mx-0 mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-green-600/20">
                    <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </div>
                  <h3 class="text-lg font-semibold text-white">Automated Alerts</h3>
                  <p class="mt-2 text-sm text-gray-400">Get notified instantly when issues arise</p>
                </div>
                
                <div class="text-center lg:text-left">
                  <div class="mx-auto lg:mx-0 mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-600/20">
                    <svg class="h-6 w-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                  </div>
                  <h3 class="text-lg font-semibold text-white">Analytics & Reports</h3>
                  <p class="mt-2 text-sm text-gray-400">Comprehensive insights and client-ready reports</p>
                </div>
              </div>
            </div>

            <!-- Right: Dashboard Preview -->
            <div class="relative hidden lg:block">
              <div class="relative rounded-2xl border border-gray-700 bg-gray-800/50 p-2 shadow-2xl backdrop-blur-sm">
                <div class="rounded-xl bg-gray-900 p-4">
                  <div class="mb-4 flex items-center gap-2">
                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                    <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                  </div>
                  <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                      <div class="h-20 rounded-lg bg-gradient-to-br from-blue-600/20 to-blue-800/20 border border-blue-500/30"></div>
                      <div class="h-20 rounded-lg bg-gradient-to-br from-green-600/20 to-green-800/20 border border-green-500/30"></div>
                      <div class="h-20 rounded-lg bg-gradient-to-br from-purple-600/20 to-purple-800/20 border border-purple-500/30"></div>
                    </div>
                    <div class="h-48 rounded-lg bg-gray-800/50 border border-gray-700"></div>
                    <div class="grid grid-cols-2 gap-4">
                      <div class="h-24 rounded-lg bg-gray-800/50 border border-gray-700"></div>
                      <div class="h-24 rounded-lg bg-gray-800/50 border border-gray-700"></div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Glow Effect -->
              <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-blue-600 via-purple-600 to-blue-600 opacity-20 blur-xl"></div>
            </div>
          </div>
        </div>
      </main>

      <!-- Footer -->
      <footer class="border-t border-gray-700/50 bg-gray-900/50 backdrop-blur-sm py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <p class="text-center text-sm text-gray-400">
            DashPilot &copy; {{ new Date().getFullYear() }}. Built with Laravel {{ laravelVersion }} and Vue 3.
          </p>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();
</script>
