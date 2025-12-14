<template>
    <div class="flex items-center justify-center min-h-[calc(100vh-12rem)]">
        <div class="w-full max-w-md">
            <div class="bg-white shadow-md rounded-lg px-8 py-10">
                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-bold text-gray-900">Login</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Sign in to access your trading account
                    </p>
                </div>

                <!-- Error Messages -->
                <div v-if="errors.length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <ul class="list-disc list-inside text-sm text-red-600">
                        <li v-for="error in errors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="handleLogin" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email Address
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="you@example.com"
                            required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading">Signing In...</span>
                        <span v-else>Sign In</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <router-link to="/register" class="font-medium text-blue-600 hover:text-blue-500">
                            Register here
                        </router-link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuth } from '../composables/useAuth';

const { errors, loading, login } = useAuth();

const form = ref({
    email: '',
    password: '',
});

const handleLogin = async () => {
    await login(form.value);
};
</script>
