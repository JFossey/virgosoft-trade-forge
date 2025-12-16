# 🚀 Usage Guide

## Seed Data & Test Accounts

The database seeder creates test users with pre-configured balances for testing:

### Test User Accounts

| Email | Password | USD Balance | BTC | ETH |
|-------|----------|-------------|-----|-----|
| `trader1@example.com` | `password` | $100,000 | 1.5 BTC | 10 ETH |
| `trader2@example.com` | `password` | $50,000 | 0.5 BTC | 5 ETH |
| `trader3@example.com` | `password` | $25,000 | 0.25 BTC | 2.5 ETH |

You can use any of these accounts to start trading immediately without manual funding.

---

## Getting Started

### 1. Login

Navigate to the application URL and login with one of the test accounts above, or create your own account.

### 2. View Your Dashboard

After login, you'll see:
- Your current USD balance
- Your asset holdings (BTC, ETH)
- Recent orders
- Current orderbook

---

## Trading Features

### Placing a Buy Order

1. Select a trading pair (BTC/USD or ETH/USD)
2. Choose **Buy** side
3. Enter your desired price (USD per coin)
4. Enter the amount (number of coins)
5. The system will calculate: `Total Cost = Price × Amount + 1.5% Commission`
6. Click **Place Order**

**Example Buy Order:**
- Buy 0.1 BTC at $50,000
- Cost: 0.1 × $50,000 = $5,000
- Commission: $5,000 × 0.015 = $75
- Total deducted: $5,075

Your USD balance will be locked until the order is matched or cancelled.

### Placing a Sell Order

1. Select a trading pair
2. Choose **Sell** side
3. Enter your asking price (USD per coin)
4. Enter the amount (number of coins)
5. The system will calculate the expected proceeds
6. Click **Place Order**

**Example Sell Order:**
- Sell 0.1 BTC at $50,000
- Proceeds: 0.1 × $50,000 = $5,000
- Commission: $5,000 × 0.015 = $75
- Net received: $4,925

Your assets will be locked until the order is matched or cancelled.

### Order Matching

Orders are matched automatically and synchronously when:
- A **buy order** finds a sell order at or below the buy price
- A **sell order** finds a buy order at or above the sell price

Matching happens immediately within the same request, and both parties are notified instantly via the real-time broadcasting system.

### Viewing Orders

The interface displays three order categories:

#### Open Orders
- Orders waiting to be matched
- Can be cancelled at any time
- Shows locked USD or assets

#### Filled Orders
- Successfully executed trades
- Shows final execution price and commission
- Cannot be modified

#### Cancelled Orders
- Orders you manually cancelled
- Locked funds/assets were released
- Historical record only

### Cancelling Orders

1. Navigate to your **Open Orders** list
2. Find the order you want to cancel
3. Click the **Cancel** button
4. Your locked USD or assets will be immediately released

---

## Understanding the Orderbook

The orderbook shows all open orders for a trading pair:

### Buy Side (Bids)
- Shows all open buy orders
- Sorted by price (highest first)
- Green color indicates buy orders

### Sell Side (Asks)
- Shows all open sell orders
- Sorted by price (lowest first)
- Red color indicates sell orders

### Spread
The difference between the highest buy price and lowest sell price indicates the current market spread.

---

## Real-Time Updates

The application uses Pusher for real-time updates. You'll receive instant notifications when:

- ✅ Your order is matched with another trader
- ✅ Your balance changes (USD or assets)
- ✅ New orders appear in the orderbook
- ✅ Orders are cancelled or filled

No page refresh needed - the interface updates automatically!

---

## Commission Structure

Every trade incurs a **1.5% commission** on the USD value:

- **Commission = Trade Value × 0.015**
- Deducted from the buyer's USD payment
- Applied automatically on order matching
- Shown in order history

**Example:**
- Trade: 0.5 BTC at $60,000 = $30,000
- Commission: $30,000 × 0.015 = $450
- Buyer pays: $30,450 total
- Seller receives: BTC proceeds (commission already taken from buyer)

---

## Tips for Testing

1. **Use Multiple Accounts** - Login with different test accounts in separate browsers to simulate real trading
2. **Try Different Prices** - Create orders at various price points to test matching logic
3. **Watch Real-Time Updates** - Keep multiple windows open to see instant updates
4. **Test Order Cancellation** - Cancel orders and verify funds are released correctly
5. **Check Balance Safety** - Try to place orders exceeding your balance to test validation

---

## Troubleshooting

### Order Not Matching
- Verify there's a counter-order at an acceptable price
- Buy orders match with sells at ≤ buy price
- Sell orders match with buys at ≥ sell price

### Real-Time Updates Not Working
- Check Pusher credentials in `.env`
- Check browser console for connection errors

### Insufficient Balance Error
- Check your available USD balance (excludes locked funds)
- Check your available assets (excludes locked amounts)
- Remember to account for 1.5% commission
