<template>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Fund Your Account</h1>
            <p class="text-gray-600 mb-6">Easily add funds to your USD balance to start trading. Please ensure you have transferred the funds before confirming.</p>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount (USD)</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input
                            type="number"
                            id="amount"
                            v-model.number="form.amount"
                            step="1"
                            min="1"
                            class="block w-full pr-10 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-500': errors.amount }"
                            placeholder="0"
                            aria-describedby="amount-currency"
                        />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm" id="amount-currency">
                                USD
                            </span>
                        </div>
                    </div>
                    <p v-if="errors.amount" class="mt-2 text-sm text-red-600">{{ errors.amount[0] }}</p>
                </div>

                <div class="flex items-center">
                    <input
                        id="confirmation"
                        type="checkbox"
                        v-model="form.confirmation"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        :class="{ 'border-red-500': errors.confirmation }"
                    />
                    <label for="confirmation" class="ml-2 block text-sm text-gray-900">
                        I confirm that I have transferred the funds to my account.
                    </label>
                </div>
                <p v-if="errors.confirmation" class="mt-2 text-sm text-red-600">{{ errors.confirmation[0] }}</p>

                <div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :class="{ 'opacity-50 cursor-not-allowed': loading }"
                    >
                        <span v-if="loading">Funding...</span>
                        <span v-else>Fund Account</span>
                    </button>
                </div>

                <div v-if="errorMessage" class="mt-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                    {{ errorMessage }}
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useTradingStore } from '../../store/trading';
import { useToast } from "vue-toastification";

const tradingStore = useTradingStore();
const router = useRouter();
const toast = useToast();

const form = ref({
    amount: 0,
    confirmation: false,
});

const loading = ref(false);
const errors = ref({});
const errorMessage = ref('');

const submitForm = async () => {
    loading.value = true;
    errors.value = {};
    errorMessage.value = '';

    try {
        const response = await axios.post('/api/account/fund', {
            amount: form.value.amount.toString(), // Send as string representation of an integer
            confirmation: form.value.confirmation,
        });

        toast.success(response.data.message);

        // Re-fetch profile to update balance
        await tradingStore.fetchProfile();

        // Clear form
        form.value.amount = 0;
        form.value.confirmation = false;

        // Redirect to dashboard immediately
        router.push({ name: 'dashboard' });

    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else if (error.response && error.response.data.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'An unexpected error occurred.';
        }
        console.error('Funding error:', error);
    } finally {
        loading.value = false;
    }
};
</script>
