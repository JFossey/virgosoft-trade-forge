import { defineStore } from 'pinia';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

export const useTradingStore = defineStore('trading', {
    state: () => ({
        profile: {
            usd_balance: '0.00',
            assets: [],
            user_id: null,
        },
        selectedSymbol: 'BTC', // Default to BTC
        orderbook: {
            buy_orders: [],
            sell_orders: [],
        },
        loading: {
            profile: false,
            orderbook: false,
            createOrder: false,
            cancelOrder: false,
        },
        errors: {
            profile: null,
            orderbook: null,
            createOrder: null,
            cancelOrder: null,
        },
    }),

    getters: {
        // Find a specific asset by its symbol
        getAsset: (state) => (symbol) => {
            return state.profile.assets.find(asset => asset.symbol === symbol);
        },
        // Get the available amount of a specific asset (not locked in orders)
        getAvailableAssetAmount: (state) => (symbol) => {
            const asset = state.profile.assets.find(asset => asset.symbol === symbol);
            return asset ? parseFloat(asset.available) : 0;
        },
    },

    actions: {
        async fetchProfile() {
            this.loading.profile = true;
            this.errors.profile = null;
            try {
                const response = await axios.get('/api/profile');
                this.profile.usd_balance = response.data.user.balance;
                this.profile.assets = response.data.assets;
                this.profile.user_id = response.data.user.id;
                console.log('Fetched user ID:', this.profile.user_id);
            } catch (error) {
                this.errors.profile = 'Failed to fetch profile.';
                toast.error('Failed to fetch profile data.');
                console.error('Failed to fetch profile:', error); // Log the full error object
            } finally {
                this.loading.profile = false;
            }
        },

        async fetchOrderbook(symbol = this.selectedSymbol) {
            this.loading.orderbook = true;
            this.errors.orderbook = null;
            try {
                const response = await axios.get(`/api/orders?symbol=${symbol}`);
                this.orderbook.buy_orders = response.data.buy_orders;
                this.orderbook.sell_orders = response.data.sell_orders;
            } catch (error) {
                this.errors.orderbook = 'Failed to fetch orderbook.';
                toast.error('Failed to fetch order book.');
                console.error('Failed to fetch orderbook:', error);
            } finally {
                this.loading.orderbook = false;
            }
        },

        setSelectedSymbol(symbol) {
            if (['BTC', 'ETH'].includes(symbol)) {
                this.selectedSymbol = symbol;
                this.fetchOrderbook(symbol); // Refresh orderbook for new symbol
            }
        },

        // Placeholder for real-time updates
        subscribeToUserChannel() {
            if (!this.profile.user_id) {
                console.warn('User ID not available for Pusher subscription.');
                return;
            }

            window.Echo.private(`user.${this.profile.user_id}`)
                .listen('.order.matched', (event) => {
                    console.log('Order Matched Event:', event);
                    this.handleOrderMatched(event);
                });
            console.log(`Subscribed to user channel: user.${this.profile.user_id}`);
        },

        unsubscribeFromUserChannel() {
            if (this.profile.user_id) {
                window.Echo.leave(`user.${this.profile.user_id}`);
                console.log(`Unsubscribed from user channel: user.${this.profile.user_id}`);
            }
        },

        handleOrderMatched(event) {
            const { trade } = event;
            toast.success(`Trade Matched! ${trade.amount} ${trade.symbol} at ${trade.price} USD`);
            // Refresh profile and orderbook after a trade
            this.fetchProfile();
            this.fetchOrderbook();
        },

        async cancelOrder(orderId) {
            this.loading.cancelOrder = true;
            this.errors.cancelOrder = null;
            try {
                const response = await axios.post(`/api/orders/${orderId}/cancel`);
                if (response.status === 200) {
                    toast.success('Order cancelled successfully.');
                    // Refresh profile and orderbook after cancellation
                    await this.fetchProfile();
                    await this.fetchOrderbook();
                }
            } catch (error) {
                this.errors.cancelOrder = 'Failed to cancel order.';
                toast.error('Failed to cancel order.');
                console.error('Failed to cancel order:', error);
            } finally {
                this.loading.cancelOrder = false;
            }
        },
    },
});
