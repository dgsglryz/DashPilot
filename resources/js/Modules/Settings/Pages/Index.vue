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
            
            <!-- Email Preview/Test -->
            <div class="mt-6 border-t border-gray-700 pt-6">
              <h4 class="text-sm font-semibold text-white mb-3">Test Email</h4>
              <div class="flex items-center gap-3">
                <select 
                  v-model="testEmailTemplate"
                  class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500"
                >
                  <option value="alert-created">Alert Created</option>
                  <option value="alert-resolved">Alert Resolved</option>
                  <option value="daily-digest">Daily Digest</option>
                </select>
                <button 
                  @click="sendTestEmail"
                  :disabled="isSendingTestEmail"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg transition-colors text-sm"
                >
                  {{ isSendingTestEmail ? 'Sending...' : 'Send Test' }}
                </button>
              </div>
              <p class="mt-2 text-xs text-gray-400">
                Test email will be sent to {{ settings.email }}
              </p>
            </div>
          </SettingsCard>
        </div>

        <!-- Webhooks Tab -->
        <div v-if="activeTab === 'webhooks'" class="space-y-6">
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

          <!-- Webhook Test Console -->
          <SettingsCard title="Webhook Test Console">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Select Webhook</label>
                <select 
                  v-model="selectedWebhookForTest"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500"
                >
                  <option value="">Select a webhook...</option>
                  <option v-for="(webhook, index) in webhooks" :key="index" :value="index">
                    {{ webhook.url || `Webhook ${index + 1}` }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Test Payload</label>
                <textarea 
                  v-model="testWebhookPayload"
                  rows="8"
                  class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm font-mono focus:outline-none focus:border-blue-500"
                  placeholder="{&quot;event&quot;: &quot;alert.created&quot;, &quot;site&quot;: &quot;example.com&quot;, &quot;message&quot;: &quot;Test alert&quot;}"
                ></textarea>
              </div>

              <div class="flex items-center gap-3">
                <button 
                  @click="testWebhook"
                  :disabled="!selectedWebhookForTest || isTestingWebhook"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors text-sm"
                >
                  {{ isTestingWebhook ? 'Testing...' : 'Test Webhook' }}
                </button>
                <button 
                  @click="loadSamplePayload"
                  class="px-4 py-2 border border-gray-700 hover:border-gray-600 text-gray-300 hover:text-white rounded-lg transition-colors text-sm"
                >
                  Load Sample
                </button>
              </div>

              <div v-if="webhookTestResult" class="mt-4 p-4 rounded-lg" :class="webhookTestResult.success ? 'bg-green-500/10 border border-green-500/30' : 'bg-red-500/10 border border-red-500/30'">
                <div class="flex items-start gap-2">
                  <CheckCircleIcon v-if="webhookTestResult.success" class="h-5 w-5 flex-shrink-0 mt-0.5 text-green-400" />
                  <XCircleIcon v-else class="h-5 w-5 flex-shrink-0 mt-0.5 text-red-400" />
                  <div class="flex-1">
                    <p class="text-sm font-semibold" :class="webhookTestResult.success ? 'text-green-400' : 'text-red-400'">
                      {{ webhookTestResult.success ? 'Success' : 'Failed' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ webhookTestResult.message }}</p>
                    <pre v-if="webhookTestResult.response" class="mt-2 text-xs text-gray-300 bg-gray-900/50 p-2 rounded overflow-auto">{{ JSON.stringify(webhookTestResult.response, null, 2) }}</pre>
                  </div>
                </div>
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
  ComputerDesktopIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Shared/Composables/useToast'

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
  { id: 'monitoring', label: 'Monitoring', icon: ChartBarIcon },
  { id: 'webhooks', label: 'Webhooks', icon: BellIcon }
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
 * Test email state
 */
const testEmailTemplate = ref('alert-created')
const isSendingTestEmail = ref(false)

/**
 * Webhook test state
 */
const selectedWebhookForTest = ref('')
const testWebhookPayload = ref('')
const isTestingWebhook = ref(false)
const webhookTestResult = ref<{ success: boolean; message: string; response?: unknown } | null>(null)

const toast = useToast()

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
const isToggling2FA = ref(false)
const toggle2FA = () => {
  isToggling2FA.value = true
  router.post('/settings/2fa/toggle', {}, {
    preserveScroll: true,
    onSuccess: () => {
      twoFactorEnabled.value = !twoFactorEnabled.value
      toast.success(twoFactorEnabled.value ? '2FA enabled successfully' : '2FA disabled successfully')
      isToggling2FA.value = false
    },
    onError: () => {
      toast.error('Failed to toggle 2FA')
      isToggling2FA.value = false
    }
  })
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

/**
 * Send test email
 */
const sendTestEmail = async () => {
  isSendingTestEmail.value = true
  try {
    await router.post('/settings/test-email', {
      template: testEmailTemplate.value
    }, {
      onSuccess: () => {
        toast.success('Test email sent successfully! Check MailHog at http://localhost:8025')
      },
      onError: () => {
        toast.error('Failed to send test email')
      }
    })
  } catch {
    toast.error('Failed to send test email')
  } finally {
    isSendingTestEmail.value = false
  }
}

/**
 * Load sample webhook payload
 */
const loadSamplePayload = () => {
  testWebhookPayload.value = JSON.stringify({
    event: 'alert.created',
    site: {
      id: 1,
      name: 'Example Site',
      url: 'https://example.com'
    },
    alert: {
      type: 'downtime',
      severity: 'critical',
      message: 'Site is down',
      timestamp: new Date().toISOString()
    }
  }, null, 2)
}

/**
 * Test webhook
 */
const testWebhook = async () => {
  if (!selectedWebhookForTest.value || selectedWebhookForTest.value === '') return
  
  const webhook = webhooks.value[Number.parseInt(selectedWebhookForTest.value)]
  if (!webhook || !webhook.url) {
    toast.error('Please select a valid webhook')
    return
  }

  isTestingWebhook.value = true
  webhookTestResult.value = null

  try {
    let payload
    try {
      payload = testWebhookPayload.value ? JSON.parse(testWebhookPayload.value) : {}
    } catch {
      toast.error('Invalid JSON payload')
      isTestingWebhook.value = false
      return
    }

    const response = await fetch('/settings/test-webhook', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        webhook_url: webhook.url,
        payload
      })
    })

    const data = await response.json()
    
    webhookTestResult.value = {
      success: response.ok,
      message: data.message || (response.ok ? 'Webhook delivered successfully' : 'Webhook delivery failed'),
      response: data
    }

    if (response.ok) {
      toast.success('Webhook test successful!')
    } else {
      toast.error('Webhook test failed')
    }
  } catch (error) {
    webhookTestResult.value = {
      success: false,
      message: 'Network error: ' + (error instanceof Error ? error.message : 'Unknown error'),
      response: null
    }
    toast.error('Webhook test failed')
  } finally {
    isTestingWebhook.value = false
  }
}
</script>
