# Guest Booking and Payment System - Implementation Documentation

## Overview

This document describes the implementation of a complete Guest Booking and Payment System for EVON electric vehicle charging stations. The system follows a 3-step checkout flow and is fully compliant with GDPR (General Data Protection Regulation).

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [3-Step Checkout Flow](#3-step-checkout-flow)
3. [Components Implemented](#components-implemented)
4. [GDPR Compliance](#gdpr-compliance)
5. [Database Schema Changes](#database-schema-changes)
6. [API Routes](#api-routes)
7. [Testing](#testing)
8. [Usage](#usage)

---

## Architecture Overview

The guest booking and payment system is built on the existing Laravel infrastructure and leverages:

- **Existing Components:**
  - [`CheckoutController`](app/Http/Controllers/Public/CheckoutController.php) - Main controller for checkout flow
  - [`PaymentGatewayService`](app/Services/PaymentGatewayService.php) - Payment gateway abstraction (CMI, Stripe)
  - [`Reservation`](app/Models/Reservation.php) - Model for storing booking data

- **New Components:**
  - [`GuestCheckoutService`](app/Services/GuestCheckoutService.php) - Business logic for guest checkout
  - Database migration for guest checkout fields

---

## 3-Step Checkout Flow

### Step 1: Duration Selection (Select Charging Duration)
- **Route:** `GET /pay/{slug}`
- **View:** [`resources/views/public/checkout/show.blade.php`](resources/views/public/checkout/show.blade.php)
- **Description:** User selects charging duration from predefined options
- **Features:**
  - Real-time price calculation via AJAX
  - Displays charge point information (power, connector type)
  - Shows pricing plan details (per minute or per kWh)

### Step 2: Personal Information (Guest Details)
- **Route:** `POST /pay/{slug}/session`
- **View:** [`resources/views/public/checkout/user-info.blade.php`](resources/views/public/checkout/user-info.blade.php)
- **Description:** User enters personal information
- **Features:**
  - GDPR consent checkboxes (required)
  - Optional marketing consent
  - Optional account creation
  - Form validation

### Step 3: Payment
- **Route:** `POST /pay/{slug}/pay`
- **View:** [`resources/views/public/checkout/payment.blade.php`](resources/views/public/checkout/payment.blade.php)
- **Description:** Secure payment processing
- **Features:**
  - CMI/Stripe integration
  - Payment status tracking
  - Receipt generation

---

## Components Implemented

### 1. GuestCheckoutService

**Location:** [`app/Services/GuestCheckoutService.php`](app/Services/GuestCheckoutService.php)

**Key Methods:**

| Method | Description |
|--------|-------------|
| `calculatePrice()` | Calculate price based on duration and tariff plan |
| `createCheckoutSession()` | Create a new checkout session with guest data |
| `validateGuestInfo()` | Validate guest information including GDPR consent |
| `getChargePointInfo()` | Get charge point information for display |
| `validateCheckoutSession()` | Validate checkout session before payment |
| `cancelCheckoutSession()` | Cancel an active checkout session |
| `getCheckoutSummary()` | Get checkout summary for display |

### 2. Enhanced CheckoutController

**Location:** [`app/Http/Controllers/Public/CheckoutController.php`](app/Http/Controllers/Public/CheckoutController.php)

**GDPR Enhancements:**
- Added GDPR consent validation in `createSession()` method
- Stores consent timestamp, IP address, and user agent
- Supports optional marketing consent

**New Method:**
- `privacyPolicy()` - Display GDPR-compliant privacy policy page

### 3. Enhanced Reservation Model

**Location:** [`app/Models/Reservation.php`](app/Models/Reservation.php)

**New Fillable Fields:**
- `is_guest` - Boolean to track guest checkouts
- `guest_info` - JSON field for guest information
- `integrator_id` - Foreign key to integrator
- `partner_id` - Foreign key to partner
- `transaction_id` - Foreign key to transaction
- `metadata` - JSON field for additional data (GDPR consent, etc.)

---

## GDPR Compliance

### Consent Management

The system collects and stores GDPR consent with the following data:

```php
$gdprConsent = [
    'consented' => true,
    'consent_timestamp' => now()->toIso8601String(),
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'privacy_policy_version' => '1.0',
    'marketing_consent' => $request->boolean('marketing_consent'),
];
```

### User Rights

The system supports the following GDPR rights:

1. **Right to Access** - Users can request their data
2. **Right to Rectification** - Users can correct their data
3. **Right to Erasure** - Users can request data deletion
4. **Right to Restrict Processing** - Users can limit data processing
5. **Right to Data Portability** - Users can export their data
6. **Right to Object** - Users can object to processing
7. **Right to Withdraw Consent** - Users can withdraw consent at any time

### Privacy Policy

A dedicated privacy policy page is available at `/privacy-policy` with:
- Detailed information about data collection
- Legal basis for processing
- Data sharing practices
- User rights explanation
- Contact information

---

## Database Schema Changes

### Migration File

**Location:** [`database/migrations/2026_03_18_000001_add_guest_checkout_fields_to_reservations_table.php`](database/migrations/2026_03_18_000001_add_guest_checkout_fields_to_reservations_table.php)

**New Columns:**

| Column | Type | Description |
|--------|------|-------------|
| `is_guest` | boolean | Guest checkout flag |
| `guest_info` | json | Guest information storage |
| `integrator_id` | unsignedBigInteger | Foreign key to integrators |
| `partner_id` | unsignedBigInteger | Foreign key to partners |
| `transaction_id` | unsignedBigInteger | Foreign key to transactions |
| `metadata` | json | Additional checkout data |

---

## API Routes

### Checkout Routes

```
GET    /pay/{slug}                        → Step 1: Duration selection
POST   /pay/{slug}/calculate               → AJAX: Price calculation
POST   /pay/{slug}/session                → Step 2: Create session
POST   /pay/{slug}/pay                    → Step 3: Initiate payment
GET    /pay/{slug}/confirm/{id}           → Payment confirmation
GET    /pay/{slug}/receipt/{id}           → Receipt page
GET    /pay/{slug}/cancel/{id}            → Payment cancellation
POST   /pay/{slug}/callback               → Payment webhook
GET    /privacy-policy                    → Privacy policy page
```

### Rate Limiting

All checkout routes are rate-limited to 60 requests per minute per IP address.

---

## Testing

### Unit Tests

**Location:** [`tests/Unit/Services/GuestCheckoutServiceTest.php`](tests/Unit/Services/GuestCheckoutServiceTest.php)

**Test Coverage:**

- Price calculation (per minute, per kWh, flat rate)
- Guest information validation
- GDPR consent validation
- Checkout session creation
- Session validation and expiration
- Session cancellation

**Running Tests:**

```bash
php artisan test --filter=GuestCheckoutServiceTest
```

---

## Usage

### For End Users

1. **Scan QR Code** - User scans QR code on charging station
2. **Select Duration** - Choose charging duration (10-150 minutes)
3. **Enter Information** - Fill in personal details and accept privacy policy
4. **Make Payment** - Complete payment via CMI or Stripe
5. **Start Charging** - Charging session automatically starts after payment

### For Administrators

1. **View Reservations** - Access guest reservations through admin panel
2. **Track Payments** - Monitor payment status in real-time
3. **Manage Sessions** - Cancel or modify active sessions
4. **Export Data** - Export booking and payment data for reporting

---

## Security Features

1. **CSRF Protection** - All forms include CSRF tokens
2. **Rate Limiting** - Prevents brute-force attacks
3. **Input Validation** - Comprehensive server-side validation
4. **Secure Payment** - SSL/TLS encrypted payment processing
5. **Session Management** - 30-minute checkout session expiration

---

## Performance Optimizations

1. **Gateway Caching** - Payment gateway resolution cached for 5 minutes
2. **Database Indexing** - Indexed columns for fast queries
3. **AJAX Calculations** - Real-time price updates without page reload

---

## Error Handling

The system handles various error scenarios:

- Invalid charge point/slug
- Unavailable charge points
- Payment failures
- Session expiration
- Network errors

---

## Changelog

### Version 1.0 (2026-03-18)

- Initial implementation
- 3-step checkout flow
- GDPR compliance
- Unit tests
- Privacy policy page

---

## Support

For questions or issues, please contact:

- **Email:** contact@evon.ma
- **Documentation:** See inline code comments

---

## License

Copyright © 2026 EVON Power. All rights reserved.
