<template>
    <div class="bg-white dark:bg-gray-700 shadow rounded-lg p-4">
        <h2 class="text-xl font-semibold mb-3 text-gray-700 dark:text-white">Your Balance</h2>
        <div v-if="tradingStore.loading.profile" class="text-gray-500 dark:text-gray-400">Loading profile...</div>
        <div v-else-if="tradingStore.errors.profile" class="text-red-500 dark:text-red-400">
            Error: {{ tradingStore.errors.profile }}
        </div>
        <div v-else>
            <p class="text-gray-600 dark:text-gray-300">USD: <span class="font-medium">${{ formatCurrency(tradingStore.profile.usd_balance) }}</span></p>
            <p v-for="asset in tradingStore.profile.assets" :key="asset.symbol" class="text-gray-600 dark:text-gray-300">
                {{ asset.symbol }}: <span class="font-medium">{{ formatCrypto(asset.amount) }}</span>
                <span v-if="asset.locked > 0" class="text-sm text-gray-500 dark:text-gray-400"> ({{ formatCrypto(asset.available) }} available)</span>
            </p>
        </div>
    </div>
</template>

<script setup>
import { useTradingStore } from '../../store/trading';
import { formatCurrency, formatCrypto } from '../../utils/formatters';

const tradingStore = useTradingStore();
</script>