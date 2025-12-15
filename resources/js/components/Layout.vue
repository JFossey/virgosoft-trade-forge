<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Navigation Bar -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo and Brand -->
                    <div class="flex items-center">
                        <router-link to="/" class="flex items-center space-x-2">
                            <HiArrowTrendingUp class="h-8 w-8 text-blue-600" />
                            <span class="text-xl font-bold text-gray-900">Trade Forge</span>
                        </router-link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="flex items-center space-x-4">
                        <!-- Show when NOT authenticated -->
                        <template v-if="!isAuthenticated">
                            <router-link
                                to="/login"
                                class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                            >
                                Login
                            </router-link>
                            <router-link
                                to="/register"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                            >
                                Register
                            </router-link>
                        </template>

                        <!-- Show when authenticated -->
                        <template v-if="isAuthenticated">
                            <router-link
                                to="/trade"
                                class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                            >
                                Trade
                            </router-link>
                            <span class="px-3 py-2 text-sm font-medium text-gray-700"> Welcome, {{ user.name }}</span>
                            <button
                                @click="handleLogout"
                                :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="loading">Logging out...</span>
                                <span v-else>Logout</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { HiArrowTrendingUp } from "vue-icons-plus/hi2";
import { useAuth } from "../composables/useAuth";

const { user, isAuthenticated, loading, logout } = useAuth();

const handleLogout = async () => {
    await logout();
};
</script>
