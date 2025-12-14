<template>
    <div class="space-y-8">
        <!-- Hero Section -->
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Welcome to Trade Forge
            </h1>
            <p class="text-lg text-gray-600 mb-8">
                Your cryptocurrency trading platform for BTC and ETH
            </p>
            <div class="flex justify-center space-x-4">
                <router-link
                    to="/register"
                    class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
                >
                    Get Started
                </router-link>
                <router-link
                    to="/login"
                    class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors cursor-pointer"
                >
                    Sign In
                </router-link>
            </div>
        </div>

        <!-- Supported Assets -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                Supported Cryptocurrencies
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                    <div class="h-12 w-12 flex items-center justify-center">
                        <SiBitcoin class="w-12 h-12 text-orange-500" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Bitcoin</h3>
                        <p class="text-sm text-gray-600">BTC/USD Trading Pairs</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                    <div class="h-12 w-12 flex items-center justify-center">
                        <SiEthereum class="w-12 h-12 text-purple-500" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Ethereum</h3>
                        <p class="text-sm text-gray-600">ETH/USD Trading Pairs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { SiBitcoin, SiEthereum } from 'vue-icons-plus/si';
</script>
