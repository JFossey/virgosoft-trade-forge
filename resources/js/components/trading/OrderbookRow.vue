<template>
    <li :class="['py-2 flex justify-between items-center text-sm', isUserOrder ? 'bg-blue-50 dark:bg-blue-900' : '']">
        <span :class="[side === 'buy' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']">
            {{ formatCurrency(order.price) }} USD
        </span>
        <span class="text-gray-800 dark:text-gray-200">
            {{ formatCrypto(order.amount) }} {{ order.symbol }}
        </span>
        <button
            v-if="isUserOrder && !tradingStore.loading.cancelOrder"
            @click="cancelOrder"
            class="ml-2 px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded focus:outline-none focus:shadow-outline"
        >
            Cancel
        </button>
        <span v-if="isUserOrder && tradingStore.loading.cancelOrder" class="ml-2 px-2 py-1 text-xs text-gray-500">
            Cancelling...
        </span>
    </li>
</template>

<script setup>
import { defineProps } from 'vue';
import { useTradingStore } from '../../store/trading';
import { formatCurrency, formatCrypto } from '../../utils/formatters';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    isUserOrder: {
        type: Boolean,
        default: false,
    },
    side: {
        type: String,
        required: true,
        validator: (value) => ['buy', 'sell'].includes(value),
    },
});

const tradingStore = useTradingStore();

const cancelOrder = async () => {
    if (confirm('Are you sure you want to cancel this order?')) {
        await tradingStore.cancelOrder(props.order.id);
    }
};
</script>
