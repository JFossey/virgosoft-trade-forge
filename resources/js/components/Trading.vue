<template>
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Trade</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Balance Summary -->
            <BalanceSummary />

            <!-- Symbol Switcher -->
            <SymbolSwitcher />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Order Forms -->
            <OrderForm />

            <!-- Orderbook -->
            <Orderbook />
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useTradingStore } from '../store/trading';
import BalanceSummary from './trading/BalanceSummary.vue';
import SymbolSwitcher from './trading/SymbolSwitcher.vue';
import Orderbook from './trading/Orderbook.vue';
import OrderForm from './trading/OrderForm.vue';

const tradingStore = useTradingStore();

onMounted(async () => {
    await tradingStore.fetchProfile();
    await tradingStore.fetchOrderbook();
    tradingStore.subscribeToUserChannel();
});

onUnmounted(() => {
    tradingStore.unsubscribeFromUserChannel();
});
</script>

<style scoped>
/* Add component-specific styles here if needed */
</style>