<template>
    <div class="bg-white dark:bg-gray-700 shadow rounded-lg p-4">
        <h2 class="text-xl font-semibold mb-3 text-gray-700 dark:text-white">
            Order Book ({{ tradingStore.selectedSymbol }})
        </h2>

        <div v-if="tradingStore.loading.orderbook" class="text-gray-500 dark:text-gray-400">Loading order book...</div>
        <div v-else-if="tradingStore.errors.orderbook" class="text-red-500 dark:text-red-400">
            Error: {{ tradingStore.errors.orderbook }}
        </div>
        <div v-else class="grid grid-cols-2 gap-4">
            <div>
                <h3 class="text-lg font-medium mb-2 text-green-600 dark:text-green-400">Buy Orders</h3>
                <ul class="divide-y divide-gray-200 dark:divide-gray-600 max-h-96 overflow-y-auto">
                    <li v-if="tradingStore.orderbook.buy_orders.length === 0" class="py-2 text-gray-500 dark:text-gray-400 text-sm">
                        No buy orders.
                    </li>
                    <OrderbookRow
                        v-for="order in tradingStore.orderbook.buy_orders"
                        :key="order.id"
                        :order="order"
                        :is-user-order="order.user_id === tradingStore.profile.user_id"
                        side="buy"
                    />
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-medium mb-2 text-red-600 dark:text-red-400">Sell Orders</h3>
                <ul class="divide-y divide-gray-200 dark:divide-gray-600 max-h-96 overflow-y-auto">
                    <li v-if="tradingStore.orderbook.sell_orders.length === 0" class="py-2 text-gray-500 dark:text-gray-400 text-sm">
                        No sell orders.
                    </li>
                    <OrderbookRow
                        v-for="order in tradingStore.orderbook.sell_orders"
                        :key="order.id"
                        :order="order"
                        :is-user-order="order.user_id === tradingStore.profile.user_id"
                        side="sell"
                    />
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useTradingStore } from '../../store/trading';
import OrderbookRow from './OrderbookRow.vue';

const tradingStore = useTradingStore();
</script>