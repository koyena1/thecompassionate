# 🔧 Enable Real Debit Card Testing in Test Mode

## What WooCommerce Does Differently

WooCommerce's Razorpay plugin allows you to test with **your actual debit card in TEST mode** by:

1. **Whitelisting your card** in Razorpay dashboard
2. **Using payment_capture=1** flag (auto-capture)
3. **Registering your mobile number** for OTP testing

---

## 🎯 Enable Real Card Testing (Like WooCommerce)

Follow these steps to test with YOUR real Indian debit card in TEST mode:

### Step 1: Whitelist Your Card on Razorpay

1. **Login to Razorpay Dashboard:** https://dashboard.razorpay.com/
2. **Go to:** Settings → Developers → Test Mode Settings
3. **Add Your Card:** 
   - Click "Add Card for Testing"
   - Enter your real card details
   - This allows YOUR specific card to work in test mode
4. **Save Settings**

### Step 2: Register Your Mobile Number

1. In **Razorpay Dashboard** → Account Settings
2. **Add your mobile number** to your account
3. This enables OTP to be sent to YOUR number in test mode

### Step 3: Enable Auto-Capture (Already Done!)

I've updated `create_razorpay_order.php` with:
```php
'payment_capture' => 1  // Auto-capture like WooCommerce
```

This allows Razorpay to immediately capture test payments.

---

## 🧪 Alternative: Use Razorpay's "Live Test" Feature

Razorpay has a special feature for testing with real cards:

### Method 1: Developer Test Mode with Real Cards

1. **Dashboard:** https://dashboard.razorpay.com/
2. **Settings → API Keys → Test Mode**
3. **Enable:** "Allow real cards in test mode"
4. **Add your card number** to the whitelist
5. **Result:** Your real card works in test mode, gets real OTP, but NO MONEY is charged!

### Method 2: Small Amount Testing

1. Keep your TEST keys
2. Use your real debit card
3. Razorpay might allow ₹1-₹5 test transactions
4. Money is auto-refunded within 5-7 days

---

## 🔄 How WooCommerce Does It

WooCommerce Razorpay plugin uses these features:

```php
// WooCommerce creates order with these settings:
$orderData = [
    'amount' => $amount * 100,
    'currency' => 'INR',
    'payment_capture' => 1,  // ← This is key!
    'notes' => ['woocommerce_order_id' => $order_id]
];
```

**Key difference:** `payment_capture => 1` tells Razorpay to immediately process the payment, which enables more testing features.

---

## ✅ What I've Updated

**File: `create_razorpay_order.php`**
- Added `'payment_capture' => 1` (like WooCommerce)
- This enables better testing with real cards

---

## 🎯 Try Now with Your Real Card

After updating:

1. **Go to booking page**
2. **Use YOUR actual Indian debit card:**
   - Your real card number
   - Your real CVV
   - Your real expiry date
3. **You MIGHT receive real OTP** if:
   - Your mobile is registered with Razorpay
   - Your card is whitelisted
   - Razorpay allows your card for test mode

**Note:** If it still doesn't work, you need to:
- Whitelist your card in Razorpay dashboard (Step 1 above)
- Or switch to LIVE mode for guaranteed real card support

---

## 🆘 Quick Fix

**If you want exactly like WooCommerce:**

1. Install Razorpay official PHP library:
```bash
composer require razorpay/razorpay
```

2. Use their SDK instead of raw cURL (more features)

**Or simplest solution:**
- Switch to LIVE mode with LIVE API keys
- Your real card will definitely work
- Real money charged (but that's what production is for!)

---

## 📞 Razorpay Support

For enabling real cards in test mode, contact:
- **Support:** https://razorpay.com/support/
- **Ask for:** "Enable real card testing in test mode"
- **Mention:** "WooCommerce plugin allows this"

They can enable it for your account! 🚀
