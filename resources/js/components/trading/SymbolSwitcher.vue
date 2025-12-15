<template>
    <div class="bg-white dark:bg-gray-700 shadow rounded-lg p-4 flex items-center justify-center">
        <button
            @click="setSymbol('BTC')"
            :class="[
                'font-bold py-2 px-4 rounded',
                tradingStore.selectedSymbol === 'BTC' ? 'bg-blue-500 hover:bg-blue-600 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white',
                'mr-2'
            ]"
        >
            Trade BTC
        </button>
        <button
            @click="setSymbol('ETH')"
            :class="[
                'font-bold py-2 px-4 rounded',
                tradingStore.selectedSymbol === 'ETH' ? 'bg-blue-500 hover:bg-blue-600 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white'
            ]"
        >
            Trade ETH
        </button>
    </div>
</template>

<script setup>
import { useTradingStore } from '../../store/trading';

const tradingStore = useTradingStore();

const setSymbol = (symbol) => {
    tradingStore.setSelectedSymbol(symbol);
};
</script>