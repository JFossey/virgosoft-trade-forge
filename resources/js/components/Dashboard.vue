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
                        <p class="mt-2 text-3xl font-bold text-gray-900">$0.00</p>
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
                        <p class="mt-2 text-3xl font-bold text-gray-900">0.00000000</p>
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
                        <p class="mt-2 text-3xl font-bold text-gray-900">0.00000000</p>
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
                <button class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors cursor-pointer">
                    Create Buy Order
                </button>
                <button class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors cursor-pointer">
                    Create Sell Order
                </button>
            </div>
        </div>

        <!-- Recent Activity Placeholder -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Activity</h2>
            <div class="text-center py-8 text-gray-500">
                <p>No recent activity</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { SiBitcoin, SiEthereum } from 'vue-icons-plus/si';
</script>
