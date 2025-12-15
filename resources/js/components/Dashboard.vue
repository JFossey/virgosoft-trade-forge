<template>
    <div class="space-y-6">
        <!-- Dashboard Header -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome to Trade Forge</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Balance Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">USD Balance</p>
                        <p v-if="tradingStore.loading.profile" class="mt-2 text-3xl font-bold text-gray-900">Loading...</p>
                        <p v-else class="mt-2 text-3xl font-bold text-gray-900">${{ formatCurrency(tradingStore.profile.usd_balance) }}</p>
                    </div>
                    <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- BTC Holdings Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">BTC Holdings</p>
                        <p v-if="tradingStore.loading.profile" class="mt-2 text-3xl font-bold text-gray-900">Loading...</p>
                        <p v-else class="mt-2 text-3xl font-bold text-gray-900">{{ formatCrypto(tradingStore.getAsset('BTC')?.amount || 0) }}</p>
                    </div>
                    <div class="h-12 w-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <SiBitcoin class="h-6 w-6 text-orange-500" />
                    </div>
                </div>
            </div>

            <!-- ETH Holdings Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">ETH Holdings</p>
                        <p v-if="tradingStore.loading.profile" class="mt-2 text-3xl font-bold text-gray-900">Loading...</p>
                        <p v-else class="mt-2 text-3xl font-bold text-gray-900">{{ formatCrypto(tradingStore.getAsset('ETH')?.amount || 0) }}</p>
                    </div>
                    <div class="h-12 w-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <SiEthereum class="h-6 w-6 text-purple-500" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button
                    @click="navigateToTrade"
                    class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
                >
                    Create Order
                </button>
                <button
                    @click="navigateToFundAccount"
                    class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors cursor-pointer"
                >
                    Fund Account
                </button>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Activity (Last 15 Minutes)</h2>

            <!-- Loading State -->
            <div v-if="tradingStore.loading.activity" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                <p class="mt-2 text-gray-500">Loading activity...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="tradingStore.errors.activity" class="text-center py-8 text-red-600">
                <p>{{ tradingStore.errors.activity }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="tradingStore.recentActivity.length === 0" class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2">No recent activity in the last 15 minutes</p>
            </div>

            <!-- Activity List -->
            <div v-else class="space-y-3">
                <ActivityItem
                    v-for="activity in tradingStore.recentActivity"
                    :key="activity.id"
                    :activity="activity"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { SiBitcoin, SiEthereum } from 'vue-icons-plus/si';
import { useTradingStore } from '../store/trading';
import { formatCurrency, formatCrypto } from '../utils/formatters';
import ActivityItem from './ActivityItem.vue';

const tradingStore = useTradingStore();
const router = useRouter();

onMounted(async () => {
    await tradingStore.fetchProfile();
    await tradingStore.fetchRecentActivity();

    // Subscribe to user's private channel for personal notifications
    tradingStore.subscribeToUserChannel();

    // Subscribe to public orderbook channels for platform-wide activity feed
    window.Echo.channel('orderbook.BTC')
        .listen('.order.created', handleOrderCreated)
        .listen('.order.matched', handleOrderMatched)
        .listen('.order.cancelled', handleOrderCancelled);

    window.Echo.channel('orderbook.ETH')
        .listen('.order.created', handleOrderCreated)
        .listen('.order.matched', handleOrderMatched)
        .listen('.order.cancelled', handleOrderCancelled);
});

onUnmounted(() => {
    // Clean up public channels
    window.Echo.leave('orderbook.BTC');
    window.Echo.leave('orderbook.ETH');
});

const handleOrderCreated = (event) => {
    const order = event?.order;
    if (!order) return;

    tradingStore.addActivityItem({
        id: `order-created-${order.id}`,
        activity_type: 'order_created',
        symbol: order.symbol,
        side: order.side,
        price: order.price,
        amount: order.amount,
        total_value: (parseFloat(order.price) * parseFloat(order.amount)).toFixed(8),
        timestamp: order.created_at,
    });
};

const handleOrderMatched = (event) => {
    const trade = event?.trade;
    if (!trade) return;

    tradingStore.addActivityItem({
        id: `trade-${trade.id}`,
        activity_type: 'trade',
        symbol: trade.symbol,
        side: 'trade',
        price: trade.price,
        amount: trade.amount,
        total_value: trade.total_value,
        commission: trade.commission,
        timestamp: trade.created_at,
    });
};

const handleOrderCancelled = (event) => {
    const order = event?.order;
    if (!order) return;

    tradingStore.addActivityItem({
        id: `order-cancelled-${order.id}`,
        activity_type: 'order_cancelled',
        symbol: order.symbol,
        side: order.side,
        price: order.price,
        amount: order.amount,
        total_value: (parseFloat(order.price) * parseFloat(order.amount)).toFixed(8),
        timestamp: order.created_at,
    });
};

const navigateToTrade = () => {
    router.push({ name: 'trade' });
};

const navigateToFundAccount = () => {
    router.push({ name: 'fundAccount' });
};
</script>
