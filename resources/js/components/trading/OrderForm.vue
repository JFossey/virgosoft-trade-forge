<template>
    <div class="bg-white dark:bg-gray-700 shadow rounded-lg p-4">
        <h2 class="text-xl font-semibold mb-3 text-gray-700 dark:text-white">Place Order</h2>
        <div class="flex mb-4 border-b border-gray-200 dark:border-gray-600">
            <button
                @click="selectedSide = 'buy'"
                :class="[
                    'flex-1 py-2 text-center font-medium focus:outline-none',
                    selectedSide === 'buy' ? 'text-green-500 border-b-2 border-green-500' : 'text-gray-500 hover:text-green-500 dark:text-gray-400 dark:hover:text-green-400'
                ]"
            >
                Buy {{ tradingStore.selectedSymbol }}
            </button>
            <button
                @click="selectedSide = 'sell'"
                :class="[
                    'flex-1 py-2 text-center font-medium focus:outline-none',
                    selectedSide === 'sell' ? 'text-red-500 border-b-2 border-red-500' : 'text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400'
                ]"
            >
                Sell {{ tradingStore.selectedSymbol }}
            </button>
        </div>

        <form @submit.prevent="submitOrder">
            <div class="mb-4">
                <label for="price" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Price (USD)</label>
                <input
                    type="number"
                    id="price"
                    v-model="order.price"
                    step="any"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline bg-gray-100 dark:bg-gray-800"
                    placeholder="0.00"
                    :disabled="tradingStore.loading.createOrder"
                >
                <p v-if="errors.price" class="text-red-500 text-xs italic">{{ errors.price }}</p>
            </div>
            <div class="mb-4">
                <label for="amount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Amount ({{ tradingStore.selectedSymbol }})</label>
                <input
                    type="number"
                    id="amount"
                    v-model="order.amount"
                    step="any"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline bg-gray-100 dark:bg-gray-800"
                    placeholder="0.00000000"
                    :disabled="tradingStore.loading.createOrder"
                >
                <p v-if="errors.amount" class="text-red-500 text-xs italic">{{ errors.amount }}</p>
            </div>
            <div v-if="selectedSide === 'buy'" class="mb-4 text-gray-600 dark:text-gray-300">
                USD Balance: {{ formatCurrency(tradingStore.profile.usd_balance) }}
            </div>
            <div v-else class="mb-4 text-gray-600 dark:text-gray-300">
                {{ tradingStore.selectedSymbol }} Available: {{ formatCrypto(tradingStore.getAvailableAssetAmount(tradingStore.selectedSymbol)) }}
            </div>

            <button
                type="submit"
                :class="[
                    'text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full',
                    selectedSide === 'buy' ? 'bg-green-500 hover:bg-green-700' : 'bg-red-500 hover:bg-red-700',
                    tradingStore.loading.createOrder ? 'opacity-50 cursor-not-allowed' : ''
                ]"
                :disabled="tradingStore.loading.createOrder"
            >
                {{ tradingStore.loading.createOrder ? 'Placing Order...' : `Place ${selectedSide === 'buy' ? 'Buy' : 'Sell'} Order` }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useTradingStore } from '../../store/trading';
import { formatCurrency, formatCrypto } from '../../utils/formatters';
import { useToast } from 'vue-toastification';
import { validateDecimalPlaces } from '../../utils/validationHelpers';

const tradingStore = useTradingStore();
const toast = useToast();

const selectedSide = ref('buy'); // 'buy' or 'sell'
const order = ref({
    price: '',
    amount: '',
});
const errors = ref({});

// Watch for changes in selectedSymbol to clear form and errors
watch(() => tradingStore.selectedSymbol, () => {
    order.value.price = '';
    order.value.amount = '';
    errors.value = {};
});

const validateForm = () => {
    errors.value = {};
    let isValid = true;

    if (!order.value.price || parseFloat(order.value.price) <= 0) {
        errors.value.price = 'Price must be a positive number.';
        isValid = false;
    }

    if (!validateDecimalPlaces(order.value.price)) {
        errors.value.price = 'Price can have a maximum of 8 decimal places.';
        isValid = false;
    }

    if (!order.value.amount || parseFloat(order.value.amount) <= 0) {
        errors.value.amount = 'Amount must be a positive number.';
        isValid = false;
    }

    if (!validateDecimalPlaces(order.value.amount)) {
        errors.value.amount = 'Amount can have a maximum of 8 decimal places.';
        isValid = false;
    }

    // Return early at this point if false
    if (isValid === false) {
        return isValid;
    }

    // Balance check
    const price = parseFloat(order.value.price);
    const amount = parseFloat(order.value.amount);

    if (selectedSide.value === 'buy') {
        const totalCost = price * amount;
        if (totalCost > parseFloat(tradingStore.profile.usd_balance)) {
            errors.value.amount = 'Insufficient USD balance.';
            isValid = false;
        }
    }

    if (selectedSide.value === 'sell') {
        const availableAssetAmount = tradingStore.getAvailableAssetAmount(tradingStore.selectedSymbol);
        if (amount > availableAssetAmount) {
            errors.value.amount = `Insufficient ${tradingStore.selectedSymbol} available.`;
            isValid = false;
        }
    }

    return isValid;
};

const submitOrder = async () => {
    if (!validateForm()) {
        toast.error('Please correct the errors in the form.');
        return;
    }

    const orderData = {
        symbol: tradingStore.selectedSymbol,
        side: selectedSide.value,
        price: parseFloat(order.value.price).toFixed(8),
        amount: parseFloat(order.value.amount).toFixed(8),
    };

    tradingStore.loading.createOrder = true;
    tradingStore.errors.createOrder = null;

    try {
        const response = await axios.post('/api/orders', orderData);
        if (response.status === 201) {
            toast.success('Order placed successfully!');
        }

        if (response.status === 200) {
            toast.success('Order matched and executed!');
        }

        order.value.price = '';
        order.value.amount = '';
        errors.value = {}; // Clear any previous errors

        // Refresh data after successful order
        await tradingStore.fetchProfile();
        await tradingStore.fetchOrderbook();

    } catch (error) {
        console.error('Order creation failed:', error);
        if (error.response && error.response.data && error.response.data.message) {
            tradingStore.errors.createOrder = error.response.data.message;
            toast.error(`Order failed: ${error.response.data.message}`);
        } else {
            tradingStore.errors.createOrder = 'Failed to place order.';
            toast.error('Failed to place order.');
        }
    } finally {
        tradingStore.loading.createOrder = false;
    }
};

</script>