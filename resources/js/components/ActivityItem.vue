<template>
    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
        <!-- Left: Icon + Details -->
        <div class="flex items-center space-x-4">
            <!-- Activity Icon -->
            <div :class="iconContainerClass">
                <svg v-if="activity.activity_type === 'trade'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="activity.activity_type === 'order_created'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Activity Details -->
            <div>
                <div class="flex items-center space-x-2">
                    <span :class="activityLabelClass" class="font-semibold text-sm">
                        {{ activityLabel }}
                    </span>
                    <span class="text-gray-600">·</span>
                    <span :class="sideClass" class="font-medium text-sm uppercase">
                        {{ activity.side }}
                    </span>
                    <span class="text-gray-600">·</span>
                    <span class="font-medium text-gray-900">{{ activity.symbol }}</span>
                </div>
                <div class="text-sm text-gray-500 mt-1">
                    {{ formatCrypto(activity.amount) }} {{ activity.symbol }} @ ${{ formatCurrency(activity.price) }}
                </div>
            </div>
        </div>

        <!-- Right: Total Value + Timestamp -->
        <div class="text-right">
            <div class="font-semibold text-gray-900">
                ${{ formatCurrency(activity.total_value) }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ timeAgo }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatCurrency, formatCrypto } from '../utils/formatters';

const props = defineProps({
    activity: {
        type: Object,
        required: true,
    },
});

const activityLabel = computed(() => {
    switch (props.activity.activity_type) {
        case 'trade': return 'Trade Executed';
        case 'order_created': return 'Order Placed';
        case 'order_cancelled': return 'Order Cancelled';
        default: return 'Activity';
    }
});

const iconContainerClass = computed(() => {
    const base = 'h-10 w-10 rounded-full flex items-center justify-center';
    switch (props.activity.activity_type) {
        case 'trade': return `${base} bg-green-100 text-green-600`;
        case 'order_created': return `${base} bg-blue-100 text-blue-600`;
        case 'order_cancelled': return `${base} bg-red-100 text-red-600`;
        default: return `${base} bg-gray-100 text-gray-600`;
    }
});

const activityLabelClass = computed(() => {
    switch (props.activity.activity_type) {
        case 'trade': return 'text-green-700';
        case 'order_created': return 'text-blue-700';
        case 'order_cancelled': return 'text-red-700';
        default: return 'text-gray-700';
    }
});

const sideClass = computed(() => {
    return props.activity.side === 'buy' ? 'text-green-600' : 'text-red-600';
});

const timeAgo = computed(() => {
    const now = new Date();
    const time = new Date(props.activity.timestamp);
    const diffMs = now - time;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins === 1) return '1 minute ago';
    if (diffMins < 60) return `${diffMins} minutes ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours === 1) return '1 hour ago';
    if (diffHours < 24) return `${diffHours} hours ago`;

    return time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
});
</script>
