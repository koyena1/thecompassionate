# 🇮🇳 Using Real Indian Debit Cards with Razorpay

## Current Status: TEST MODE
You're currently using Razorpay **TEST mode**, which only accepts test cards.

---

## 🧪 Option 1: Test with Indian Test Cards

Use these cards in TEST mode (no real money charged):

### Card 1 - Indian Debit (Success with OTP)
- **Card Number:** `5267 3181 8797 5449`
- **CVV:** `123`
- **Expiry:** `12/26`
- **OTP:** `1234` (any 4 digits work in test mode)

### Card 2 - Indian Credit (Success with OTP)
- **Card Number:** `4012 0010 3714 1112`
- **CVV:** `123`
- **Expiry:** `12/26`
- **OTP:** `1234`

### Card 3 - Indian Rupay Card
- **Card Number:** `6074 8262 4022 0003`
- **CVV:** `123`
- **Expiry:** `12/26`

---

## 💳 Option 2: Use REAL Indian Debit/Credit Cards (LIVE MODE)

To accept real payments from actual Indian cards:

### Step 1: Activate Razorpay Live Account
1. Login to **Razorpay Dashboard**: https://dashboard.razorpay.com/
2. Complete KYC verification (requires business documents)
3. Wait for account activation (usually 24-48 hours)

### Step 2: Generate LIVE API Keys
1. Go to **Settings → API Keys** in Razorpay Dashboard
2. Click **Generate Live Keys**
3. Copy both:
   - **Key ID** (starts with `rzp_live_...`)
   - **Key Secret** (secret string)

### Step 3: Update Configuration
1. Open: `patient/payment_config.php`
2. Replace with your LIVE keys:
   ```php
   define('RAZORPAY_KEY_ID', 'rzp_live_YOUR_LIVE_KEY_HERE');
   define('RAZORPAY_KEY_SECRET', 'YOUR_LIVE_SECRET_HERE');
   ```

### Step 4: Test with Real Money
- Use your actual Indian debit/credit card
- Real OTP will be sent to your registered mobile
- Real money will be charged (₹700)

---

## 🎯 Quick Test Right Now

**Try this Indian test card (works in TEST mode):**

1. Go to booking page
2. Fill appointment form
3. Click "Proceed to Payment"
4. Enter these details in Razorpay modal:
   - Card: **5267 3181 8797 5449**
   - CVV: **123**
   - Expiry: **12/26**
   - Name: Any name
5. When OTP screen appears, enter: **1234**
6. Payment should succeed!

---

## 📱 Other Indian Payment Methods

Razorpay supports these popular Indian payment methods:

### UPI (Recommended for Indian users)
- Google Pay
- PhonePe
- Paytm
- BHIM UPI

### Netbanking
- SBI, HDFC, ICICI, Axis
- All major Indian banks

### Wallets
- Paytm Wallet
- PhonePe Wallet
- Amazon Pay

**Note:** These methods are available in both TEST and LIVE mode on Razorpay checkout.

---

## ❓ FAQ

**Q: Why doesn't my real debit card work?**  
A: Your Razorpay account is in TEST mode. Real cards only work in LIVE mode after KYC verification.

**Q: Is test mode safe?**  
A: Yes! No real money is charged in test mode. It's for development only.

**Q: How long does KYC take?**  
A: Usually 1-2 business days after submitting all required documents.

**Q: Can I test without real money?**  
A: Yes! Use the Indian test cards listed above.

---

## 🆘 Need Help?

- **Razorpay Support:** https://razorpay.com/support/
- **Test Cards List:** https://razorpay.com/docs/payments/payments/test-card-details/
- **KYC Guide:** https://razorpay.com/docs/partners/onboarding/kyc/

---

**Current Setup:**
- Mode: TEST
- Key: rzp_test_RsjLwhfFu01NNS
- Test cards will work
- Real cards will NOT work until you switch to LIVE mode
