# Axdoro-Lite – AXDORO Men's T-Shirts E-Commerce Platform
**Lean E-Commerce MVP Build Package (₹10,000 Scope Cap)**
**Delivered by:** PeoplePoint Consultants for **AXDORO Clothing / Apparel**

---

## 📌 Project Overview

This repository contains the complete production-ready WordPress + WooCommerce deliverables and interactive preview prototype for **AXDORO**, built strictly according to the finalized Lean E-Commerce Website Build Plan.

It adheres to the non-negotiable architectural mandates:
- **Production Engine:** WordPress + WooCommerce + Blocksy Free + Gutenberg.
- **Single Source of Truth:** All products, variations, inventory, customers, orders, checkout, and tracking reside directly inside WooCommerce.
- **Catalogue:** 100% Men's T-Shirts only (26 curated cuts across Oversized 240 GSM, Boxy Half Sleeve, Graphic & Back-Print, Acid & Vintage Wash, and Heavy Plain & Pocket).
- **Shopping Flow:** Mandatory **Select Size → Add to Bag** direct flow on every product card with live tactile confirmations.
- **Payment Integrity:** Unique WooCommerce order generated *before* payment; automated webhook verification + zero-cost WhatsApp UPI / manual UTR fallback (integrated with WhatsApp 8807304713).
- **Fulfillment & Tracking:** Shiprocket-ready custom order statuses and customer milestone tracker [axdoro_track_order].
- **Lean AI Support:** Client-side zero-cost FAQ chatbot with WhatsApp human handoff.

---

## 🗂️ Repository Directory Structure

`	ext
Axdoro-Lite/
│
├── preview/                                  # Interactive Live Preview Prototype
│   ├── index.html                            # Full-featured luxury storefront (Men's T-Shirts, Size -> Add to Bag)
│   ├── server.js                             # Lightweight local preview HTTP server
│   └── images/                               # Bespoke studio product photography
│
├── wp-content/
│   ├── plugins/
│   │   └── axdoro-commerce/                  # Custom AXDORO WooCommerce Plugin
│   │       ├── axdoro-commerce.php           # Plugin bootstrap, hooks, HPOS compatibility
│   │       ├── includes/
│   │       │   ├── class-axdoro-activator.php# Custom order statuses (Awaiting Payment, Shipped, etc.)
│   │       │   ├── class-axdoro-logger.php   # WC_Logger wrapper for webhooks & debugging
│   │       │   ├── class-whatsapp-handoff.php# WhatsApp payment link & UTR fallback handler
│   │       │   ├── class-payment-webhook.php # REST endpoint /wp-json/axdoro/v1/payment-webhook
│   │       │   ├── class-order-meta.php      # Custom order fields (UTR, transaction ID, AWB)
│   │       │   ├── class-tracking.php        # Shiprocket AWB tracking & [axdoro_track_order]
│   │       │   ├── class-admin-settings.php  # WP Admin settings screen under WooCommerce
│   │       │   └── class-ai-faq-chatbot.php  # Lean AI / FAQ chatbot with WhatsApp handoff
│   │       └── assets/
│   │           ├── css/
│   │           │   ├── axdoro-commerce.css   # Styling for buttons, tracking & checkout
│   │           │   └── axdoro-chatbot.css    # Clean styling for floating AI assistant
│   │           └── js/
│   │               ├── axdoro-chatbot.js     # Instant keyword FAQ bot + WhatsApp escalation
│   │               └── axdoro-tracking.js    # AJAX order tracking
│   │
│   └── themes/
│       └── axdoro-child/                     # Blocksy Child Theme
│           ├── style.css                     # AXDORO design system (Warm Ivory, Charcoal, Muted Gold)
│           ├── functions.php                 # Layout hooks, mobile CTA, WhatsApp size helper
│
├── product-import/
│   ├── axdoro_sample_products_import.csv     # 154 rows WooCommerce variable products import
│   └── WOOCOMMERCE_CSV_IMPORT_GUIDE.md       # Client mapping guide for 200+ final catalog
│
├── page-templates/                           # Gutenberg Block Patterns for 5 Primary Pages
│   ├── home-page-pattern.html                # Home page layout
│   ├── about-page-pattern.html               # About AXDORO brand & 240 GSM story
│   ├── custom-orders-page-pattern.html       # Bulk enquiry form & WhatsApp redirect
│   └── contact-page-pattern.html             # Contact channels & FAQ layout
│
├── legal-templates/                          # Client-ready policy drafts
│   ├── privacy-policy.md                     # Privacy Policy
│   ├── terms-and-conditions.md               # Terms & Conditions
│   ├── shipping-policy.md                    # Shipping & Delivery Policy
│   └── return-and-refund-policy.md           # Return, Exchange & Refund Policy
│
├── DEPLOYMENT_GUIDE.md                       # Complete 10-14 day production guide & checklist
└── README.md                                 # Master documentation index (this file)
`

---

## 🚀 Quick Start Deployment

1. **Setup WordPress:** Install WordPress 6.x and WooCommerce on the client's hosting.
2. **Install Theme:** Install free **Blocksy** from WP theme directory, then copy wp-content/themes/axdoro-child into /wp-content/themes/ and activate it.
3. **Install Plugin:** Copy wp-content/plugins/axdoro-commerce into /wp-content/plugins/ and activate **AXDORO Commerce Core**.
4. **Import Sample Products:** Go to **WooCommerce > Products > Import** and select product-import/axdoro_sample_products_import.csv.
5. **Create 5 Primary Pages:** In WordPress Page Editor, paste the block pattern HTML from page-templates/ into Home, About, Custom Orders, and Contact pages.
6. **Set up Track Order Page:** Create /track-order/ and insert shortcode [axdoro_track_order].
7. **Configure Payment & WhatsApp:** Navigate to **WooCommerce > AXDORO Settings** to input the client's WhatsApp number (8807304713) and webhook credentials.
8. Follow [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for full testing and verification.
