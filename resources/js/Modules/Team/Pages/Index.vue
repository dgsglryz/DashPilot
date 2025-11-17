<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Team Members</h1>
                    <p class="text-gray-400 mt-1">
                        Manage your team and access permissions
                    </p>
                </div>
                <button
                    @click="showInviteModal = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
                >
                    <UserPlusIcon class="w-5 h-5" />
                    Invite Member
                </button>
            </div>

            <!-- Team Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div
                    class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-5"
                >
                    <p class="text-gray-400 text-sm">Total Members</p>
                    <p class="text-2xl font-bold text-white mt-1">
                        {{ stats.total }}
                    </p>
                </div>
                <div
                    class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-5"
                >
                    <p class="text-gray-400 text-sm">Admins</p>
                    <p class="text-2xl font-bold text-white mt-1">
                        {{ stats.admins }}
                    </p>
                </div>
                <div
                    class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-5"
                >
                    <p class="text-gray-400 text-sm">Active</p>
                    <p class="text-2xl font-bold text-white mt-1">
                        {{ stats.active }}
                    </p>
                </div>
                <div
                    class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-5"
                >
                    <p class="text-gray-400 text-sm">Pending Invites</p>
                    <p class="text-2xl font-bold text-white mt-1">
                        {{ stats.pending }}
                    </p>
                </div>
            </div>

            <!-- Team Members List -->
            <div
                class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 overflow-hidden"
            >
                <table class="w-full">
                    <thead class="bg-gray-900/50">
                        <tr>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                            >
                                Member
                            </th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                            >
                                Role
                            </th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                            >
                                Last Active
                            </th>
                            <th
                                class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        <tr
                            v-for="member in members"
                            :key="member.id"
                            class="hover:bg-gray-700/20 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold"
                                    >
                                        {{ getInitials(member.name) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-white">
                                            {{ member.name }}
                                        </p>
                                        <p class="text-sm text-gray-400">
                                            {{ member.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="getRoleBadgeClass(member.role)"
                                >
                                    {{ member.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="getStatusBadgeClass(member.status)"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="
                                            getStatusDotClass(member.status)
                                        "
                                    ></span>
                                    {{ member.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-400 text-sm">{{
                                    formatRelativeTime(member.lastActiveAt)
                                }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        @click="openTasksPopup(member)"
                                        class="p-2 hover:bg-gray-700 rounded-lg transition-colors"
                                        title="View tasks"
                                    >
                                        <DocumentTextIcon
                                            class="w-4 h-4 text-green-400"
                                        />
                                    </button>
                                    <button
                                        v-if="member.id !== currentUser.id"
                                        @click="openMessagePopup(member)"
                                        class="p-2 hover:bg-gray-700 rounded-lg transition-colors"
                                        title="Send message"
                                    >
                                        <ChatBubbleLeftIcon
                                            class="w-4 h-4 text-blue-400"
                                        />
                                    </button>
                                    <button
                                        v-if="member.id !== currentUser.id"
                                        @click="removeMember(member.id)"
                                        class="p-2 hover:bg-gray-700 rounded-lg transition-colors"
                                        title="Remove member"
                                    >
                                        <TrashIcon
                                            class="w-4 h-4 text-gray-400"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Invite Modal -->
        <InviteMemberModal
            :show="showInviteModal"
            @close="showInviteModal = false"
            @invite="sendInvite"
        />

        <!-- Message Popup -->
        <MessagePopup
            :is-open="showMessagePopup"
            :recipient="selectedRecipient"
            @close="closeMessagePopup"
        />

        <!-- Tasks Popup -->
        <MemberTasksPopup
            :is-open="showTasksPopup"
            :member="selectedMember"
            @close="closeTasksPopup"
        />
    </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Shared/Layouts/AppLayout.vue";
import InviteMemberModal from "@/Modules/Team/Components/InviteMemberModal.vue";
import MessagePopup from "@/Modules/Team/Components/MessagePopup.vue";
import MemberTasksPopup from "@/Modules/Team/Components/MemberTasksPopup.vue";
import {
    UserPlusIcon,
    TrashIcon,
    ChatBubbleLeftIcon,
    DocumentTextIcon,
} from "@heroicons/vue/24/outline";

/**
 * Component props from Inertia
 * @property {Array} members - Team members list
 * @property {Object} stats - Team statistics
 * @property {Object} currentUser - Current authenticated user
 */
defineProps({
    members: {
        type: Array,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    currentUser: {
        type: Object,
        required: true,
    },
});

/**
 * Local state
 */
const showInviteModal = ref(false);
const showMessagePopup = ref(false);
const selectedRecipient = ref(null);
const showTasksPopup = ref(false);
const selectedMember = ref(null);

/**
 * Get user initials
 * @param {string} name - Full name
 * @returns {string} Initials
 */
const getInitials = (name) => {
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
};

/**
 * Get role badge class
 * @param {string} role - User role
 * @returns {string} Tailwind classes
 */
const getRoleBadgeClass = (role) => {
    const classes = {
        admin: "bg-red-500/10 text-red-400",
        manager: "bg-blue-500/10 text-blue-400",
        member: "bg-gray-500/10 text-gray-400",
    };
    return classes[role] || classes.member;
};

/**
 * Get status badge class
 * @param {string} status - User status
 * @returns {string} Tailwind classes
 */
const getStatusBadgeClass = (status) => {
    const classes = {
        active: "bg-green-500/10 text-green-400",
        pending: "bg-yellow-500/10 text-yellow-400",
        inactive: "bg-gray-500/10 text-gray-400",
    };
    return classes[status] || classes.inactive;
};

/**
 * Get status dot class
 * @param {string} status - User status
 * @returns {string} Tailwind classes
 */
const getStatusDotClass = (status) => {
    const classes = {
        active: "bg-green-400",
        pending: "bg-yellow-400",
        inactive: "bg-gray-400",
    };
    return classes[status] || classes.inactive;
};

/**
 * Format relative time
 * @param {string} timestamp - ISO timestamp
 * @returns {string} Formatted time
 */
const formatRelativeTime = (timestamp) => {
    if (!timestamp) return "Never";

    const now = new Date();
    const past = new Date(timestamp);
    const diffMs = now - past;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;

    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays}d ago`;
};

/**
 * Send invite to new member
 * @param {Object} data - Invite data
 */
const sendInvite = (data) => {
    router.post("/team/invite", data, {
        onSuccess: () => {
            showInviteModal.value = false;
        },
    });
};

/**
 * Remove member
 * @param {number} memberId - Member ID
 */
const removeMember = (memberId) => {
    if (confirm("Are you sure you want to remove this member?")) {
        router.delete(`/team/${memberId}`);
    }
};

/**
 * Open message popup for a member
 * @param {Object} member - Member object
 */
const openMessagePopup = (member) => {
    selectedRecipient.value = member;
    showMessagePopup.value = true;
};

/**
 * Close message popup
 */
const closeMessagePopup = () => {
    showMessagePopup.value = false;
    selectedRecipient.value = null;
};

/**
 * Open tasks popup for a member
 * @param {Object} member - Member object
 */
const openTasksPopup = (member) => {
    selectedMember.value = member;
    showTasksPopup.value = true;
};

/**
 * Close tasks popup
 */
const closeTasksPopup = () => {
    showTasksPopup.value = false;
    selectedMember.value = null;
};
</script>
