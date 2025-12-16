# 🚀 Usage Guide

## Seed Data & Test Accounts

The database seeder creates test users with pre-configured balances for testing:

### Test User Accounts

| Name | Email | Password | USD Balance | BTC | ETH |
|------|-------|----------|-------------|-----|-----|
| Test User 1 | `user1@example.com` | `password` | $100,000 | 10 BTC | 100 ETH |
| Test User 2 | `user2@example.com` | `password` | $200,000 | 20 BTC | 200 ETH |
| Test User 3 | `user3@example.com` | `password` | $300,000 | 30 BTC | 300 ETH |

You can use any of these accounts to start trading immediately without manual funding.

---

## Suggested Usage

To test the system it is recommended to have two browser windows open, ether of two different browser EG: Chrome & Firefox or one normal and one in private/incognito mode.

Two usage scenarios:

1. Logging in with the same user in both windows.
2. Logging in as different users in the each window.

---

## Getting Started

### 1. Login/Register

Navigate to the application URL and login with one of the test accounts above, or create your own account.

Alternatively, register a new account.

### 2. Dashboard Overview

After login, the dashboard displays:

- **USD Balance** - Your available USD funds
- **BTC Holdings** - Total BTC amount you own
- **ETH Holdings** - Total ETH amount you own
- **Recent Activity** - Last 15 minutes of trading activity from all users

---

## Managing Your Account

### Funding Your Account

Click the **"Fund Account"** button to add USD to your balance:

1. Click **Fund Account** button
2. Enter the amount you want to add
3. Submit to instantly credit your account
4. Your USD balance updates immediately

**Note:** Test accounts are pre-funded.

---

## Trading

### Accessing the Trading Page

Click the **"Create Order"** button on the dashboard to access the buy/sell trading interface.

### Trading Interface Features

The trading page includes:
- **Symbol Selector** - Switch between BTC and ETH trading pairs
- **Buy/Sell Toggle** - Choose whether to buy or sell
- **Price Input** - Enter your desired price per coin (USD)
- **Amount Input** - Enter the quantity you want to trade
- **Your Orders** - View your own buy and sell orders for the selected symbol
- **Order Submission** - Place your order with automatic validation

### Placing a Buy Order

1. Navigate to the **Create Order** page
2. Select your trading pair (BTC or ETH)
3. Ensure **Buy** tab selected
4. Enter your price
5. Enter the amount you want to buy
6. Click to place the order

The portion of your USD balance is locked until the order matches or you cancel it.

### Placing a Sell Order

1. Navigate to the **Create Order** page
2. Select your trading pair (BTC or ETH)
3. Switch to **Sell** tab
4. Enter your asking price
5. Enter the amount you want to sell
6. Click to place the order

The portion of your assets are locked until the order matches or you cancel it.

### Viewing Your Orders

On the **Create Order** page, you can see:

- **Your Buy Orders** - All your open/filled/cancelled buy orders for the selected symbol
- **Your Sell Orders** - All your open/filled/cancelled sell orders for the selected symbol

Switch between BTC and ETH to view orders for different trading pairs.

### Order Statuses

- **Open** - Waiting to be matched, funds/assets are locked
- **Filled** - Successfully matched and executed
- **Cancelled** - You cancelled the order, funds/assets released

---

## Real-Time Updates

The application uses Pusher for live updates without page refresh:

### Where Real-Time Updates Appear

**Create Order Page:**

- If you have the same user open in multiple browser windows/tabs
- Your open orders list update instantly across all windows

**Recent Activity (Dashboard):**

- Shows trading activity from **all users** in the last 15 minutes
- Updates in real-time as any user places or matches orders
- You can watch the market activity without refreshing

---

## Commission Structure

Every trade incurs a **1.5% commission** on the USD value:

- **Commission = Trade Value × 0.015**
- Deducted from the buyer's USD payment
- Applied automatically when orders match
- Shown in your order history

**Example:**
- Trade: 2 BTC at $60,000 = $120,000
- Commission: $120,000 × 0.015 = $1,800
- Buyer pays: $121,800 total
- Seller receives BTC proceeds (commission already taken from buyer)

---

## Troubleshooting

### Real-Time Updates Not Working

- Verify Pusher credentials in `.env`
- Check browser console for WebSocket connection errors
- Ensure your queues are being processed using the `sync` driver

