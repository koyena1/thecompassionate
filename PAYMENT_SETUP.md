# Payment Gateway Integration Setup Guide

## 📋 Overview
This integration adds Razorpay and Cashfree payment gateways to the appointment booking system with automatic email invoices.

## 🗄️ Database Setup

### Step 1: Run SQL Script
Execute the `payment_schema.sql` file in phpMyAdmin:

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select `safe_space_db` database
3. Click on "SQL" tab
4. Copy and paste contents from `payment_schema.sql`
5. Click "Go" to execute

This adds the following columns to `appointments` table:
- `payment_status` - Status of payment (pending/paid/failed)
- `payment_amount` - Amount paid (default ₹700.00)
- `payment_id` - Payment gateway order ID
- `payment_gateway` - Gateway used (Razorpay/Cashfree)
- `payment_date` - Timestamp of successful payment
- `transaction_id` - Gateway transaction reference
- `invoice_number` - Unique invoice number

## 🔑 Payment Gateway Configuration

### Step 2: Configure Razorpay

1. Sign up at https://razorpay.com/
2. Go to Dashboard → Settings → API Keys
3. Generate API Keys (Test mode)
4. Open `patient/payment_config.php`
5. Update the following:
```php
define('RAZORPAY_KEY_ID', 'your_razorpay_key_id_here');
define('RAZORPAY_KEY_SECRET', 'your_razorpay_key_secret_here');
```

### Step 3: Configure Cashfree

1. Sign up at https://merchant.cashfree.com/
2. Go to Developers → API Keys
3. Get your App ID and Secret Key (Test mode)
4. Open `patient/payment_config.php`
5. Update the following:
```php
define('CASHFREE_APP_ID', 'your_cashfree_app_id_here');
define('CASHFREE_SECRET_KEY', 'your_cashfree_secret_key_here');
define('CASHFREE_ENV', 'TEST'); // Change to 'PROD' for production
```

### Step 4: Configure Admin Email

Open `patient/payment_config.php` and update:
```php
define('ADMIN_EMAIL', 'admin@yourdomain.com'); // Replace with actual admin email
```

## 📧 Email Configuration

The system uses existing PHPMailer setup from `mailer.php`. Ensure it's configured correctly:

1. Gmail SMTP is already configured
2. Email: thecompassionatespace49@gmail.com
3. App Password is set
4. Emails will be sent to both patient and admin after successful payment

## 🚀 How It Works

### Payment Flow:

1. **Patient fills appointment form** → Selects date, time, health issue, and payment gateway
2. **Clicks "Proceed to Payment"** → Creates appointment record with status "Pending Payment"
3. **Payment gateway opens** → Razorpay or Cashfree modal/page
4. **Patient completes payment** → Gateway sends callback to `payment_callback.php`
5. **Payment verified** → Updates appointment status to "Confirmed"
6. **Invoices sent** → Emails sent to both patient and admin
7. **Redirect to success page** → Shows booking confirmation

## 📁 Files Created/Modified

### New Files:
- `payment_schema.sql` - Database schema updates
- `patient/payment_config.php` - Payment gateway configuration
- `patient/payment_callback.php` - Handles payment verification and emails
- `patient/booking_success.php` - Success page after payment
- `PAYMENT_SETUP.md` - This file

### Modified Files:
- `patient/book_appointment.php` - Updated with payment integration

## 💰 Payment Amount

Default consultation fee: **₹700.00**

To change the fee, edit `patient/payment_config.php`:
```php
define('APPOINTMENT_FEE', 700.00); // Change amount here
```

## 🧪 Testing

### Test Mode:
1. Both gateways are set to TEST mode by default
2. Use test cards provided by Razorpay/Cashfree
3. No real money is charged

### Razorpay Test Cards:
- Card Number: 4111 1111 1111 1111
- CVV: Any 3 digits
- Expiry: Any future date

### Cashfree Test Cards:
Check Cashfree documentation for test card numbers

## 🔐 Security Notes

1. **Never commit API keys to Git** - Add `payment_config.php` to `.gitignore`
2. **Use environment variables** in production
3. **Enable SSL/HTTPS** before going live
4. **Validate webhook signatures** - Already implemented in callback
5. **Server-side validation** - All payment verifications done server-side

## 📊 Admin Features

Admins receive:
- Email notification for every successful booking
- Complete invoice with payment details
- Transaction ID for reference
- Patient information

## 🐛 Troubleshooting

### Payment not processing:
- Check API keys are correct
- Ensure `payment_callback.php` is accessible
- Check PHP error logs
- Verify database columns exist

### Emails not sending:
- Check `mailer.php` configuration
- Verify Gmail app password
- Check spam folder
- Enable less secure apps in Gmail (if needed)

### Database errors:
- Run `payment_schema.sql` again
- Check column names match
- Verify database connection

## 🌐 Going Live

Before production:
1. Switch to LIVE API keys
2. Change `CASHFREE_ENV` to 'PROD'
3. Update return URLs to production domain
4. Enable SSL certificate
5. Test thoroughly with small amounts
6. Set up webhook URLs in gateway dashboards

## 📞 Support

For issues:
- Razorpay: https://razorpay.com/support/
- Cashfree: https://docs.cashfree.com/
- PHPMailer: Check EMAIL_SETUP_README.md

## ✅ Checklist

- [ ] Database updated with payment_schema.sql
- [ ] Razorpay API keys configured
- [ ] Cashfree API keys configured
- [ ] Admin email configured
- [ ] PHPMailer working
- [ ] Test payment completed successfully
- [ ] Email invoices received
- [ ] Appointment status updated to "Confirmed"

---

**Version:** 1.0  
**Last Updated:** December 18, 2025
