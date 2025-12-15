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
        supportedSymbols: ['BTC', 'ETH'], // Easy to extend in the future
        orderbook: {
            buy_orders: [],
            sell_orders: [],
        },
        recentActivity: [],
        publicChannel: null,
        privateChannel: null,
        loading: {
            profile: false,
            orderbook: false,
            createOrder: false,
            cancelOrder: false,
            activity: false,
        },
        errors: {
            profile: null,
            orderbook: null,
            createOrder: null,
            cancelOrder: null,
            activity: null,
        },
    }),

    getters: {
        // Find a specific asset by its symbol (with safety)
        getAsset: (state) => (symbol) => {
            return state.profile.assets.find(asset => asset.symbol === symbol);
        },
        // Get the available amount of a specific asset (not locked in orders)
        getAvailableAssetAmount: (state) => (symbol) => {
            const asset = state.profile.assets.find(asset => asset.symbol === symbol);
            return asset?.available ? parseFloat(asset.available) : 0;
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
            } catch (error) {
                const errMsg = error.response?.data?.message || 'Failed to fetch profile data.';
                this.errors.profile = errMsg;
                toast.error(errMsg);
                console.error('Failed to fetch profile:', error);
            } finally {
                this.loading.profile = false;
            }
        },

        async fetchOrderbook(symbol = this.selectedSymbol) {
            if (!symbol) return;

            this.loading.orderbook = true;
            this.errors.orderbook = null;
            try {
                const response = await axios.get(`/api/orders?symbol=${symbol}`);
                this.orderbook.buy_orders = response.data.buy_orders;
                this.orderbook.sell_orders = response.data.sell_orders;
            } catch (error) {
                const errMsg = error.response?.data?.message || 'Failed to fetch order book.';
                this.errors.orderbook = errMsg;
                toast.error(errMsg);
                console.error('Failed to fetch orderbook:', error);
            } finally {
                this.loading.orderbook = false;
            }
        },

        setSelectedSymbol(symbol) {
            if (!this.supportedSymbols.includes(symbol)) {
                console.warn(`Unsupported symbol: ${symbol}. Falling back to default.`);
                symbol = 'BTC';
            }

            // Unsubscribe from previous public channel if exists
            if (this.publicChannel) {
                const oldChannelName = `orderbook.${this.selectedSymbol}`;
                window.Echo.leave(oldChannelName);
            }

            this.selectedSymbol = symbol;

            // Subscribe to new public channel and fetch fresh orderbook
            this.subscribeToPublicOrderbookChannel(symbol);
            this.fetchOrderbook(symbol);
        },

        subscribeToPublicOrderbookChannel(symbol) {
            if (!symbol) {
                console.warn('Symbol not available for public channel subscription.');
                return;
            }

            // Clean up any existing public channel
            if (this.publicChannel) {
                window.Echo.leave(`orderbook.${this.selectedSymbol}`);
            }

            const channelName = `orderbook.${symbol}`;
            this.publicChannel = window.Echo.channel(channelName)
                .listen('.order.created', () => this.refreshOrderbook())
                .listen('.order.cancelled', () => this.refreshOrderbook())
                .listen('.order.matched', () => this.refreshOrderbook())
                .error((error) => {
                    console.error(`Failed to subscribe to ${channelName}:`, error);
                });
        },

        subscribeToUserChannel() {
            if (!this.profile.user_id) {
                console.warn('User ID not available for private channel subscription.');
                return;
            }

            // Clean up any existing private channel
            if (this.privateChannel) {
                window.Echo.leave(`user.${this.profile.user_id}`);
            }

            const channelName = `user.${this.profile.user_id}`;
            this.privateChannel = window.Echo.private(channelName)
                .listen('.order.created', (event) => {
                    const order = event?.order;
                    if (!order) {
                        console.error('Invalid order data received', event);
                        return;
                    }
                    toast.success(`Order created! ${order.side.toUpperCase()} ${order.amount} ${order.symbol} at ${order.price} USD`);

                    // Add order creation to activity feed
                    this.addActivityItem({
                        id: `order-${order.id}`,
                        activity_type: 'order_created',
                        symbol: order.symbol,
                        side: order.side,
                        price: order.price,
                        amount: order.amount,
                        total_value: (parseFloat(order.price) * parseFloat(order.amount)).toFixed(8),
                        timestamp: order.created_at,
                    });
                })
                .listen('.order.matched', (event) => {
                    const trade = event?.trade;
                    if (!trade) {
                        console.error('Invalid trade data received', event);
                        return;
                    }
                    toast.success(`Trade Matched! ${trade.amount} ${trade.symbol} at ${trade.price} USD`);
                    this.fetchProfile();
                    this.refreshOrderbook();

                    // Add trade to activity feed
                    this.addActivityItem({
                        id: `trade-${trade.id}`,
                        activity_type: 'trade',
                        symbol: trade.symbol,
                        side: trade.buyer_id === this.profile.user_id ? 'buy' : 'sell',
                        price: trade.price,
                        amount: trade.amount,
                        total_value: trade.total_value,
                        commission: trade.commission,
                        timestamp: trade.created_at,
                    });
                })
                .listen('.order.cancelled', (event) => {
                    const order = event?.order;
                    if (!order) {
                        console.error('Invalid order data received', event);
                        return;
                    }
                    toast.info(`Your ${order.side} order #${order.id} was cancelled.`);
                    this.fetchProfile();
                    this.refreshOrderbook();

                    // Add cancelled order to activity feed
                    this.addActivityItem({
                        id: `order-${order.id}`,
                        activity_type: 'order_cancelled',
                        symbol: order.symbol,
                        side: order.side,
                        price: order.price,
                        amount: order.amount,
                        total_value: (parseFloat(order.price) * parseFloat(order.amount)).toFixed(8),
                        timestamp: order.created_at,
                    });
                })
                .error((error) => {
                    console.error(`Failed to subscribe to private channel ${channelName}:`, error);
                });
        },

        // Simple debounce helper (no external lib needed)
        refreshOrderbook: (() => {
            let timeout;
            return function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.fetchOrderbook(), 300);
            };
        })(),

        unsubscribeAllChannels() {
            if (this.privateChannel) {
                window.Echo.leave(`user.${this.profile.user_id}`);
                this.privateChannel = null;
            }
            if (this.publicChannel) {
                window.Echo.leave(`orderbook.${this.selectedSymbol}`);
                this.publicChannel = null;
            }
        },

        async cancelOrder(orderId) {
            this.loading.cancelOrder = true;
            this.errors.cancelOrder = null;
            try {
                const response = await axios.post(`/api/orders/${orderId}/cancel`);
                if (response.status === 200) {
                    toast.success('Order cancelled successfully.');
                    // Fallback manual refresh in case Pusher event is delayed/missed
                    this.fetchProfile();
                    this.refreshOrderbook();
                }
            } catch (error) {
                const errMsg = error.response?.data?.message || 'Failed to cancel order.';
                this.errors.cancelOrder = errMsg;
                toast.error(errMsg);
                console.error('Failed to cancel order:', error);
            } finally {
                this.loading.cancelOrder = false;
            }
        },

        async fetchRecentActivity() {
            this.loading.activity = true;
            this.errors.activity = null;
            try {
                const response = await axios.get('/api/activity');
                this.recentActivity = response.data.data;
            } catch (error) {
                const errMsg = error.response?.data?.message || 'Failed to fetch activity.';
                this.errors.activity = errMsg;
                toast.error(errMsg);
                console.error('Failed to fetch activity:', error);
            } finally {
                this.loading.activity = false;
            }
        },

        addActivityItem(item) {
            // Prepend to beginning (most recent first)
            this.recentActivity.unshift(item);

            // Trim to last 50 items
            if (this.recentActivity.length > 50) {
                this.recentActivity = this.recentActivity.slice(0, 50);
            }
        },
    },
});