<template>
  <AppLayout>
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold text-white">Settings</h1>
        <p class="text-gray-400 mt-1">Manage your account and application preferences</p>
      </div>

      <!-- Settings Navigation -->
      <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 overflow-hidden">
        <nav class="flex gap-6 px-6 py-4 overflow-x-auto">
          <button 
            v-for="tab in settingsTabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors whitespace-nowrap"
            :class="activeTab === tab.id 
              ? 'bg-blue-600 text-white' 
              : 'text-gray-400 hover:text-white hover:bg-gray-700'"
          >
            <component :is="tab.icon" class="w-5 h-5" />
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="space-y-6">
        <!-- General Settings -->
        <div v-if="activeTab === 'general'" class="space-y-6">
          <SettingsCard title="Profile Information">
            <form @submit.prevent="saveProfile" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                  <input 
                    v-model="profileForm.name"
                    type="text"
                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                  <input 
                    v-model="profileForm.email"
                    type="email"
                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Company Name</label>
                <input 
                  v-model="profileForm.company"
                  type="text"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                />
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                  Save Changes
                </button>
              </div>
            </form>
          </SettingsCard>

          <SettingsCard title="Timezone & Language">
            <form @submit.prevent="savePreferences" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">Timezone</label>
                  <select 
                    v-model="profileForm.timezone"
                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  >
                    <option value="UTC">UTC</option>
                    <option value="America/New_York">Eastern Time</option>
                    <option value="America/Chicago">Central Time</option>
                    <option value="America/Los_Angeles">Pacific Time</option>
                    <option value="Europe/London">London</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">Language</label>
                  <select 
                    v-model="profileForm.language"
                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  >
                    <option value="en">English</option>
                    <option value="es">Spanish</option>
                    <option value="fr">French</option>
                    <option value="de">German</option>
                  </select>
                </div>
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                  Save Preferences
                </button>
              </div>
            </form>
          </SettingsCard>
        </div>

        <!-- Notifications Settings -->
        <div v-if="activeTab === 'notifications'" class="space-y-6">
          <SettingsCard title="Email Notifications">
            <div class="space-y-4">
              <NotificationToggle 
                v-model="notificationSettings.emailAlerts"
                title="Alert Notifications"
                description="Receive emails when critical alerts are detected"
              />
              <NotificationToggle 
                v-model="notificationSettings.emailReports"
                title="Weekly Reports"
                description="Get weekly summary reports via email"
              />
              <NotificationToggle 
                v-model="notificationSettings.emailDowntime"
                title="Downtime Alerts"
                description="Immediate notification when sites go down"
              />
            </div>
          </SettingsCard>

          <SettingsCard title="Webhook Configuration">
            <div class="space-y-4">
              <div v-for="(webhook, index) in webhooks" :key="index" class="p-4 bg-gray-900/50 rounded-lg">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <input 
                      v-model="webhook.url"
                      type="url"
                      placeholder="https://your-webhook-url.com/endpoint"
                      class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500"
                    />
                  </div>
                  <button 
                    @click="removeWebhook(index)"
                    class="ml-3 p-2 hover:bg-gray-700 rounded transition-colors"
                  >
                    <TrashIcon class="w-4 h-4 text-gray-400" />
                  </button>
                </div>
                
                <div class="flex items-center gap-4 text-sm">
                  <label class="flex items-center gap-2">
                    <input 
                      v-model="webhook.events"
                      type="checkbox"
                      value="downtime"
                      class="rounded bg-gray-800 border-gray-700 text-blue-600"
                    />
                    <span class="text-gray-300">Downtime</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input 
                      v-model="webhook.events"
                      type="checkbox"
                      value="alerts"
                      class="rounded bg-gray-800 border-gray-700 text-blue-600"
                    />
                    <span class="text-gray-300">Alerts</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input 
                      v-model="webhook.events"
                      type="checkbox"
                      value="reports"
                      class="rounded bg-gray-800 border-gray-700 text-blue-600"
                    />
                    <span class="text-gray-300">Reports</span>
                  </label>
                </div>
              </div>

              <button 
                @click="addWebhook"
                class="w-full py-2 border-2 border-dashed border-gray-700 hover:border-blue-500 rounded-lg text-gray-400 hover:text-white transition-colors"
              >
                + Add Webhook
              </button>

              <div class="flex justify-end pt-2">
                <button 
                  @click="saveWebhooks"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                  Save Webhooks
                </button>
              </div>
            </div>
          </SettingsCard>
        </div>

        <!-- Security Settings -->
        <div v-if="activeTab === 'security'" class="space-y-6">
          <SettingsCard title="Change Password">
            <form @submit.prevent="changePassword" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Current Password</label>
                <input 
                  v-model="passwordForm.currentPassword"
                  type="password"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                <input 
                  v-model="passwordForm.newPassword"
                  type="password"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password</label>
                <input 
                  v-model="passwordForm.newPassword_confirmation"
                  type="password"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                />
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                  Update Password
                </button>
              </div>
            </form>
          </SettingsCard>

          <SettingsCard title="Two-Factor Authentication">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-white font-medium">Enable 2FA</p>
                <p class="text-sm text-gray-400 mt-1">Add an extra layer of security to your account</p>
              </div>
              <button 
                @click="toggle2FA"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
              >
                {{ twoFactorEnabled ? 'Disable' : 'Enable' }}
              </button>
            </div>
          </SettingsCard>

          <SettingsCard title="Active Sessions">
            <div class="space-y-3">
              <div v-for="session in activeSessions" :key="session.id" class="flex items-center justify-between p-4 bg-gray-900/50 rounded-lg">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <ComputerDesktopIcon class="w-5 h-5 text-blue-400" />
                  </div>
                  <div>
                    <p class="text-white font-medium">{{ session.device }}</p>
                    <p class="text-sm text-gray-400">{{ session.location }} • {{ session.lastActive }}</p>
                  </div>
                </div>
                <button 
                  v-if="!session.current"
                  @click="revokeSession(session.id)"
                  class="px-3 py-1.5 bg-red-600/10 hover:bg-red-600/20 text-red-400 rounded-lg text-sm transition-colors"
                >
                  Revoke
                </button>
                <span v-else class="px-3 py-1.5 bg-green-500/10 text-green-400 rounded-lg text-sm">
                  Current
                </span>
              </div>
            </div>
          </SettingsCard>
        </div>

        <!-- Monitoring Settings -->
        <div v-if="activeTab === 'monitoring'" class="space-y-6">
          <SettingsCard title="Check Intervals">
            <form @submit.prevent="saveMonitoring" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Default Check Interval</label>
                <select 
                  v-model="monitoringSettings.checkInterval"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                >
                  <option value="1">1 minute</option>
                  <option value="5">5 minutes</option>
                  <option value="10">10 minutes</option>
                  <option value="30">30 minutes</option>
                  <option value="60">1 hour</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Timeout Duration</label>
                <select 
                  v-model="monitoringSettings.timeout"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                >
                  <option value="5">5 seconds</option>
                  <option value="10">10 seconds</option>
                  <option value="30">30 seconds</option>
                  <option value="60">1 minute</option>
                </select>
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                  Save Settings
                </button>
              </div>
            </form>
          </SettingsCard>

          <SettingsCard title="Alert Thresholds">
            <form @submit.prevent="saveThresholds" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">Uptime Threshold (%)</label>
                  <input 
                    v-model.number="monitoringSettings.uptimeThreshold"
                    type="number"
                    min="0"
                    max="100"
                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">Response Time Threshold (ms)</label>
                  <input 
                    v-model.number="monitoringSettings.responseTimeThreshold"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  />
                </div>
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                  Save Thresholds
                </button>
              </div>
            </form>
          </SettingsCard>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Shared/Layouts/AppLayout.vue'
import SettingsCard from '@/Modules/Settings/Components/SettingsCard.vue'
import NotificationToggle from '@/Modules/Settings/Components/NotificationToggle.vue'
import { 
  UserCircleIcon,
  BellIcon,
  ShieldCheckIcon,
  ChartBarIcon,
  TrashIcon,
  ComputerDesktopIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props from Inertia
 * @property {Object} settings - User settings
 */
const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
})

/**
 * Settings tabs
 */
const settingsTabs = [
  { id: 'general', label: 'General', icon: UserCircleIcon },
  { id: 'notifications', label: 'Notifications', icon: BellIcon },
  { id: 'security', label: 'Security', icon: ShieldCheckIcon },
  { id: 'monitoring', label: 'Monitoring', icon: ChartBarIcon }
]

/**
 * Local state
 */
const activeTab = ref('general')
const twoFactorEnabled = ref(false)

/**
 * Profile form
 */
const profileForm = reactive({
  name: props.settings.name || '',
  email: props.settings.email || '',
  company: props.settings.company || '',
  timezone: props.settings.timezone || 'UTC',
  language: props.settings.language || 'en'
})

/**
 * Notification settings
 */
const notificationSettings = reactive({
  emailAlerts: props.settings.emailAlerts ?? true,
  emailReports: props.settings.emailReports ?? true,
  emailDowntime: props.settings.emailDowntime ?? true
})

/**
 * Webhooks
 */
const webhooks = ref(props.settings.webhooks || [])

/**
 * Password form
 */
const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  newPassword_confirmation: ''
})

/**
 * Monitoring settings
 */
const monitoringSettings = reactive({
  checkInterval: props.settings.checkInterval || '5',
  timeout: props.settings.timeout || '10',
  uptimeThreshold: props.settings.uptimeThreshold || 95,
  responseTimeThreshold: props.settings.responseTimeThreshold || 2000
})

/**
 * Active sessions mock data
 */
const activeSessions = ref([
  { id: 1, device: 'Chrome on Windows', location: 'New York, US', lastActive: '5m ago', current: true },
  { id: 2, device: 'Safari on MacOS', location: 'Los Angeles, US', lastActive: '2h ago', current: false }
])

/**
 * Save profile
 */
const saveProfile = () => {
  router.post('/settings/profile', profileForm)
}

/**
 * Save preferences
 */
const savePreferences = () => {
  router.post('/settings/preferences', profileForm)
}

/**
 * Add webhook
 */
const addWebhook = () => {
  webhooks.value.push({ url: '', events: [] })
}

/**
 * Remove webhook
 * @param {number} index - Webhook index
 */
const removeWebhook = (index) => {
  webhooks.value.splice(index, 1)
}

/**
 * Save webhooks
 */
const saveWebhooks = () => {
  router.post('/settings/webhooks', { webhooks: webhooks.value })
}

/**
 * Change password
 */
const changePassword = () => {
  router.post('/settings/password', passwordForm)
}

/**
 * Toggle 2FA
 */
const toggle2FA = () => {
  router.post('/settings/2fa/toggle')
  twoFactorEnabled.value = !twoFactorEnabled.value
}

/**
 * Revoke session
 * @param {number} sessionId - Session ID
 */
const revokeSession = (sessionId) => {
  router.delete(`/settings/sessions/${sessionId}`)
}

/**
 * Save monitoring settings
 */
const saveMonitoring = () => {
  router.post('/settings/monitoring', monitoringSettings)
}

/**
 * Save thresholds
 */
const saveThresholds = () => {
  router.post('/settings/thresholds', monitoringSettings)
}
</script>
