<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 translate-y-4 scale-95"
    enter-to-class="opacity-100 translate-y-0 scale-100"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0 scale-100"
    leave-to-class="opacity-0 translate-y-4 scale-95"
  >
    <div
      v-if="isOpen"
      class="fixed bottom-6 right-6 w-96 h-[600px] bg-gray-800 rounded-xl shadow-2xl border border-gray-700/50 flex flex-col z-50"
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-700/50 bg-gray-900/50">
        <div class="flex items-center gap-3">
          <div
            v-if="recipient"
            class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold"
          >
            {{ getInitials(recipient.name) }}
          </div>
          <div>
            <h3 class="text-white font-semibold">
              {{ recipient ? recipient.name : 'Messages' }}
            </h3>
            <p v-if="recipient" class="text-xs text-gray-400">{{ recipient.email }}</p>
          </div>
        </div>
        <button
          @click="closePopup"
          class="p-2 hover:bg-gray-700 rounded-lg transition-colors"
        >
          <XMarkIcon class="w-5 h-5 text-gray-400" />
        </button>
      </div>

      <!-- Messages List -->
      <div
        ref="messagesContainer"
        class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-900/30"
      >
        <div v-if="loading" class="text-center text-gray-400 py-8">
          Loading messages...
        </div>
        <div v-else-if="messages.length === 0" class="text-center text-gray-400 py-8">
          No messages yet. Start the conversation!
        </div>
        <div
          v-for="message in messages"
          :key="message.id"
          class="flex"
          :class="message.is_sender ? 'justify-end' : 'justify-start'"
        >
          <div
            class="max-w-[75%] rounded-lg px-4 py-2"
            :class="
              message.is_sender
                ? 'bg-blue-600 text-white'
                : 'bg-gray-700 text-gray-100'
            "
          >
            <p class="text-sm whitespace-pre-wrap break-words">{{ message.content }}</p>
            <p
              class="text-xs mt-1"
              :class="message.is_sender ? 'text-blue-200' : 'text-gray-400'"
            >
              {{ formatTime(message.created_at) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div v-if="recipient" class="p-4 border-t border-gray-700/50 bg-gray-900/50">
        <form @submit.prevent="sendMessage" class="flex gap-2">
          <textarea
            v-model="messageContent"
            @keydown.enter.exact.prevent="sendMessage"
            @keydown.enter.shift.exact="messageContent += '\n'"
            rows="2"
            class="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 resize-none"
            placeholder="Type a message..."
            :disabled="sending"
          ></textarea>
          <button
            type="submit"
            :disabled="!messageContent.trim() || sending"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg transition-colors flex items-center justify-center"
          >
            <PaperAirplaneIcon
              v-if="!sending"
              class="w-5 h-5"
            />
            <span v-else class="text-sm">Sending...</span>
          </button>
        </form>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
/**
 * MessagePopup Component
 * 
 * Displays a chat popup for team messaging functionality.
 * Features:
 * - Real-time message loading and polling
 * - Send/receive messages with a team member
 * - Auto-scroll to latest message
 * - Error handling with user feedback
 * - Loading states and disabled states during operations
 * 
 * @component
 */
import { ref, watch, nextTick, onMounted, onUnmounted, type Ref } from 'vue'
import { XMarkIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline'

/**
 * Message interface
 */
interface Message {
    id: number
    content: string
    sender_id: number
    recipient_id: number
    is_sender: boolean
    sender_name: string
    created_at: string
    is_read: boolean
}

/**
 * Recipient user interface
 */
interface Recipient {
    id: number
    name: string
    email: string
}

/**
 * Component props
 */
const props = defineProps<{
    /**
     * Whether the popup is visible
     */
    isOpen: boolean
    /**
     * The recipient user object (null when no recipient selected)
     */
    recipient: Recipient | null
}>()

/**
 * Component emits
 */
const emit = defineEmits<{
    /**
     * Emitted when popup should be closed
     */
    close: []
}>()

/**
 * Local reactive state
 */
const messages: Ref<Message[]> = ref([])
const messageContent: Ref<string> = ref('')
const loading: Ref<boolean> = ref(false)
const sending: Ref<boolean> = ref(false)
const messagesContainer: Ref<HTMLElement | null> = ref(null)
let pollInterval: ReturnType<typeof setInterval> | null = null

/**
 * Get user initials from full name for avatar display.
 * 
 * @param {string} name - Full name of the user
 * @returns {string} Uppercase initials (max 2 characters)
 * 
 * @example
 * getInitials('John Doe') // Returns 'JD'
 * getInitials('Alice') // Returns 'A'
 */
const getInitials = (name: string): string => {
    if (!name) return ''
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
}

/**
 * Format timestamp for human-readable display.
 * Shows relative time (e.g., "5m ago") or absolute time for older messages.
 * 
 * @param {string} timestamp - ISO 8601 timestamp string
 * @returns {string} Formatted time string
 * 
 * @example
 * formatTime('2024-11-17T10:30:00Z') // Returns 'Just now' or '5m ago' or 'Nov 17, 10:30 AM'
 */
const formatTime = (timestamp: string): string => {
    if (!timestamp) return ''
    
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    
    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    
    const diffHours = Math.floor(diffMins / 60)
    if (diffHours < 24) return `${diffHours}h ago`
    
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    })
}

/**
 * Load conversation messages
 */
const loadMessages = async () => {
  if (!props.recipient) return
  
  loading.value = true
  try {
    const response = await fetch(`/messages/conversation/${props.recipient.id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })
    
    if (response.ok) {
      const data = await response.json()
      messages.value = data.messages || []
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Failed to load messages:', error)
  } finally {
    loading.value = false
  }
}

/**
 * Send a message
 */
const sendMessage = async () => {
  if (!messageContent.value.trim() || !props.recipient || sending.value) return
  
  sending.value = true
  const content = messageContent.value.trim()
  messageContent.value = ''
  
  try {
    const response = await fetch('/messages/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        recipient_id: props.recipient.id,
        content: content,
      }),
    })
    
    if (response.ok) {
      const data = await response.json()
      messages.value.push(data.message)
      await nextTick()
      scrollToBottom()
    } else {
      // Restore message content on error
      messageContent.value = content
      const errorData = await response.json()
      alert(errorData.error || 'Failed to send message')
    }
  } catch (error) {
    console.error('Failed to send message:', error)
    messageContent.value = content
    alert('Failed to send message. Please try again.')
  } finally {
    sending.value = false
  }
}

/**
 * Scroll messages container to bottom
 */
const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

/**
 * Close popup
 */
const closePopup = () => {
  emit('close')
}

/**
 * Start polling for new messages
 */
const startPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
  
  pollInterval = setInterval(() => {
    if (props.isOpen && props.recipient) {
      loadMessages()
    }
  }, 5000) // Poll every 5 seconds
}

/**
 * Stop polling
 */
const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

// Watch for recipient changes
watch(() => props.recipient, (newRecipient) => {
  if (newRecipient && props.isOpen) {
    loadMessages()
    startPolling()
  } else {
    stopPolling()
  }
}, { immediate: true })

// Watch for popup open/close
watch(() => props.isOpen, (isOpen) => {
  if (isOpen && props.recipient) {
    loadMessages()
    startPolling()
  } else {
    stopPolling()
    messages.value = []
    messageContent.value = ''
  }
})

onMounted(() => {
  if (props.isOpen && props.recipient) {
    loadMessages()
    startPolling()
  }
})

onUnmounted(() => {
  stopPolling()
})
</script>

