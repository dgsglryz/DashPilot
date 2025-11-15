<script setup lang="ts">
import { computed, ref } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

type ScheduledCheck = {
    date: string;
    title?: string;
    subtitle?: string | null;
    tag?: string | null;
    status?: 'info' | 'success' | 'warning' | 'danger';
};

const props = withDefaults(
    defineProps<{
        scheduledChecks: ScheduledCheck[];
    }>(),
    {
        scheduledChecks: () => [],
    },
);

const currentDate = ref(new Date());

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days: Array<{ day: number | ''; isCurrentMonth: boolean }> = [];

    for (let i = 0; i < startingDayOfWeek; i += 1) {
        days.push({ day: '', isCurrentMonth: false });
    }

    for (let i = 1; i <= daysInMonth; i += 1) {
        days.push({ day: i, isCurrentMonth: true });
    }

    return days;
});

const monthYear = computed(() =>
    currentDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }),
);

const eventsByDay = computed(() => {
    const map = new Map<number, ScheduledCheck[]>();
    const targetYear = currentDate.value.getFullYear();
    const targetMonth = currentDate.value.getMonth();

    const fallbackEvents: ScheduledCheck[] = [
        {
            date: new Date(targetYear, targetMonth, 4).toISOString(),
            title: 'Site Monitoring',
            subtitle: '15 active sites',
            tag: 'Uptime 99.5%',
            status: 'info',
        },
        {
            date: new Date(targetYear, targetMonth, 10).toISOString(),
            title: 'SEO Performance',
            subtitle: 'Revenue overview',
            tag: '$5,000',
            status: 'warning',
        },
        {
            date: new Date(targetYear, targetMonth, 15).toISOString(),
            title: 'Alerts',
            subtitle: 'Open issues',
            tag: '3 alerts pending',
            status: 'danger',
        },
        {
            date: new Date(targetYear, targetMonth, 19).toISOString(),
            title: 'Recent Activities',
            subtitle: 'Weekly summary',
            tag: '5 PM sync',
            status: 'success',
        },
    ];

    const source: ScheduledCheck[] =
        props.scheduledChecks.length > 0 ? props.scheduledChecks : fallbackEvents;

    source.forEach((event) => {
        const date = new Date(event.date);
        if (date.getFullYear() !== targetYear || date.getMonth() !== targetMonth) {
            return;
        }

        const day = date.getDate();
        if (!map.has(day)) {
            map.set(day, []);
        }

        map.get(day)?.push(event);
    });

    return map;
});

const eventClasses: Record<string, string> = {
    info: 'bg-blue-500/20 text-blue-300',
    success: 'bg-emerald-500/20 text-emerald-300',
    warning: 'bg-yellow-500/20 text-yellow-300',
    danger: 'bg-red-500/20 text-red-300',
};

const previousMonth = (): void => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
};

const nextMonth = (): void => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
};
</script>

<template>
  <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
    
    <!-- Calendar Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-semibold text-white">Dashboard</h3>
      
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-400 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          {{ monthYear }}
        </span>
        
        <div class="flex items-center gap-1">
          <button
            @click="previousMonth"
            class="p-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors"
          >
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <button
            @click="nextMonth"
            class="p-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors"
          >
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
      
      <!-- Weekday Headers -->
      <div 
        v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']"
        :key="day"
        class="text-center text-xs font-medium text-gray-500 py-2"
      >
        {{ day }}
      </div>

      <!-- Calendar Days -->
      <div
        v-for="(dayObj, index) in calendarDays"
        :key="index"
        class="min-h-[90px] rounded-lg p-2 transition-colors"
        :class="dayObj.isCurrentMonth ? 'bg-gray-900 hover:bg-gray-700' : 'bg-transparent'"
      >
        <div v-if="dayObj.isCurrentMonth" class="flex h-full flex-col">
          <span class="text-sm font-medium text-gray-300">{{ dayObj.day }}</span>

          <div
            v-for="event in eventsByDay.get(dayObj.day as number) ?? []"
            :key="`${event.title}-${event.tag}`"
            class="mt-2 rounded px-2 py-1 text-xs"
            :class="eventClasses[event.status ?? 'info']"
          >
            <p class="font-semibold">
              {{ event.title }}
            </p>
            <p v-if="event.subtitle" class="text-gray-200/80">
              {{ event.subtitle }}
            </p>
            <p v-if="event.tag" class="text-[11px] text-gray-100/70">
              {{ event.tag }}
            </p>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>
