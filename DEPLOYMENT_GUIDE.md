# AXDORO Production Deployment & Operations Guide
**Lean E-Commerce Build Plan (₹10,000 MVP)**
**Platform:** WordPress + WooCommerce + Blocksy Free + Gutenberg

---

## 1. Executive Summary & Commercial Boundary

This deployment manual details the end-to-end launch procedure for **AXDORO Clothing / Apparel** within the approved ₹10,000 development cap and 10–14 working day timeline. 

### Core Architectural Rules:
- **Single Source of Truth:** WordPress + WooCommerce manages all products, variations, inventory, orders, checkout, and customer accounts.
- **Zero Framework Bloat:** No external headless React/Vite production frontend. Native Gutenberg blocks and Blocksy Free theme ensure lightning-fast mobile performance without recurring page-builder license fees.
- **Order-First Payment Integrity:** The website generates the unique WooCommerce Order ID (`#1024`) *before* the customer is routed to payment via WhatsApp/UPI.
- **Automated Verification + Zero-Cost Fallback:** Webhook auto-verification via Razorpay/Cashfree with immediate manual UTR verification fallback so launch is never delayed by gateway approvals.

---

## 2. 10–14 Working Day Delivery Roadmap

| Phase | Days | Workstream | Key Deliverables |
| :--- | :--- | :--- | :--- |
| **Phase 1** | Day 1–2 | Hosting, CMS & Theme Setup | Domain DNS point, SSL active, WordPress 6.x installed, Blocksy theme + `axdoro-child` activated. |
| **Phase 2** | Day 3–4 | 5 Primary Navigation Pages | Gutenberg patterns imported for Home, Shop, About, Custom/Bulk Orders, Contact & Support. |
| **Phase 3** | Day 5–6 | Commerce & Catalogue Import | WooCommerce configured. Sample products imported via `axdoro_sample_products_import.csv`. Client 200+ sheet mapped. |
| **Phase 4** | Day 7–8 | WhatsApp & Payment Webhook | `axdoro-commerce` plugin installed. WhatsApp UPI handoff, UPI intent links, and webhook endpoint configured. |
| **Phase 5** | Day 9–10 | Shipping & Support Setup | Shiprocket connection configured. Track Order `[axdoro_track_order]` live. AI FAQ bot and WhatsApp handoff active. |
| **Phase 6** | Day 11–12 | Mobile QA & Security Audit | 2-column mobile grid, 44px tap targets, caching, SSL, and payment test runs. |
| **Phase 7** | Day 13–14 | UAT & Admin Handover | Client walkthrough, admin credential delivery, and production go-live. |

---

## 3. Recommended Hosting & Environment (Budget-Friendly)

To maintain the ₹10,000 budget cap, host on reliable, cost-effective Indian or global WordPress hosting:
- **Recommended Hosts:** Hostinger Business WordPress, HostGator India Cloud, or Bluehost India (approx. ₹2,500–₹3,500/year, billed directly to client).
- **PHP Version:** PHP 8.1 or 8.2 with `opcache`, `curl`, `mbstring`, `openssl`, and `gd`/`imagick`.
- **Database:** MySQL 8.0+ or MariaDB 10.6+.
- **SSL:** Free Let's Encrypt SSL enabled.
- **CDN:** Cloudflare Free tier for DNS, DDoS mitigation, and global edge caching.

---

## 4. Step-by-Step Installation Instructions

### Step 1: Install Required Free Plugins
Navigate to **Plugins > Add New** in WordPress and install the following lightweight plugins:
1. **WooCommerce** (Core e-commerce foundation)
2. **Blocksy Companion** (Optional free companion for Blocksy header/footer builder)
3. **YITH WooCommerce Wishlist** (or **TI WooCommerce Wishlist**) (Free tier for save-for-later functionality)
4. **WP Armour – Honeypot Anti Spam** (Zero-bloat spam protection for contact & bulk forms)
5. **LiteSpeed Cache** (or **WP Super Cache**) (Free page & browser caching)
6. **UpdraftPlus** (Free automated backups to Google Drive)

### Step 2: Activate AXDORO Child Theme
1. Upload the folder `wp-content/themes/axdoro-child/` to your server under `/wp-content/themes/`.
2. Go to **Appearance > Themes** and activate **AXDORO Child**.
3. Confirm that the parent theme **Blocksy** is installed in the themes directory.

### Step 3: Install & Activate Custom Commerce Plugin
1. Upload `wp-content/plugins/axdoro-commerce/` to `/wp-content/plugins/`.
2. Go to **Plugins > Installed Plugins** and activate **AXDORO Commerce Core**.
3. Upon activation, the plugin automatically:
   - Registers custom order statuses (`Awaiting Payment`, `Payment Verified`, `Packed`, `Shipped`, `Out for Delivery`).
   - Declares HPOS (High-Performance Order Storage) compatibility.
   - Enqueues the mobile sticky Add to Bag action, WhatsApp size helper, and floating AI/FAQ chatbot.

### Step 4: Configure AXDORO Settings
Navigate to **WooCommerce > AXDORO Settings**:
- **WhatsApp Business Number:** Enter client's 10-digit number with country code (e.g. `918807304713`).
- **Merchant UPI VPA:** Enter official brand VPA (e.g. `axdoro@upi`).
- **Merchant Display Name:** Enter `AXDORO Clothing`.
- **Active Payment Mode:** Choose `whatsapp_upi` (Direct UPI + UTR) or `razorpay` (Payment Links + Webhook).
- **Webhook Secret:** Copy the auto-generated secret string for gateway webhook settings.
- **Shiprocket Mode:** Select `manual` or `native_plugin`.

---

## 5. Payment Gateway Webhook Configuration

### Option A: Razorpay Automated Webhook
1. Log into **Razorpay Dashboard > Settings > Webhooks**.
2. Click **Add New Webhook**.
3. **Webhook URL:** Enter `https://your-domain.com/wp-json/axdoro/v1/payment-webhook`
4. **Secret:** Paste the exact Webhook Secret from **WooCommerce > AXDORO Settings**.
5. **Active Events:** Check `payment.captured` and `payment_link.paid`.
6. Save the webhook.

### Option B: Zero-Cost WhatsApp UPI + UTR Fallback (Immediate Launch)
If payment gateway onboarding is pending or delayed:
1. Set **Active Payment Mode** to `WhatsApp Direct UPI`.
2. When customer clicks "Place Order" at checkout:
   - WooCommerce creates the order (`#1024`) with status *Awaiting Payment*.
   - Thank You page opens with two primary buttons: **"Pay via WhatsApp"** (with prefilled message) and **"Pay via UPI App"** (`upi://pay`).
   - Customer completes UPI transfer and submits their 12-digit UTR on the screen.
   - The UTR is immediately recorded in order meta and private order notes.
   - Admin verifies in bank app and marks order as *Payment Verified* with one click.

---

## 6. Shiprocket Logistics Integration

1. Create a free business account at [shiprocket.in](https://www.shiprocket.in).
2. **Native Plugin Sync:**
   - Install **Shiprocket Official WooCommerce Plugin** from WP plugin repository.
   - Authorize API connection in Shiprocket panel (**Channels > Add Channel > WooCommerce**).
   - Orders with status *Payment Verified* or *Processing* automatically sync to Shiprocket.
3. **Tracking Milestone Display:**
   - Create a page at `/track-order/` and paste the shortcode: `[axdoro_track_order]`.
   - When AWB number is generated (automatically or entered into the order metabox), customers can track their parcel through the visual 5-step milestone timeline.

---

## 7. Pre-Go-Live Testing Checklist

### Website & Responsive UI
- [ ] Top announcement bar displays launch promo / free shipping above ₹999.
- [ ] 5 Primary navigation pages (Home, Shop, About, Custom Orders, Contact) accessible on desktop and mobile.
- [ ] Mobile 2-column shop grid renders cleanly without horizontal scrolling.
- [ ] Tap targets on size selectors and buttons exceed 44px height.

### Catalogue & Discovery
- [ ] Sample products imported and tested across variations (XS, S, M, L, XL, XXL).
- [ ] Category archive pages (`/product-category/oversized/`, etc.) load properly.
- [ ] Search icon in header functions with keyword queries.
- [ ] "Need Help with Size?" WhatsApp button on single product page generates accurate prefilled text with product title and SKU.

### Commerce & Checkout
- [ ] Add to Bag adds correct color/size variation.
- [ ] Cart slide-out/page displays correct pricing and subtotal.
- [ ] Checkout validates mandatory WhatsApp mobile number and PIN code.
- [ ] Placing order successfully creates unique WooCommerce order in database before payment.

### Payment & Fulfillment
- [ ] Thank You page displays "Complete Payment via WhatsApp / UPI" container.
- [ ] Direct UPI link (`upi://pay`) triggers mobile UPI apps on phone.
- [ ] Customer UTR submission form saves reference to order note without errors.
- [ ] Webhook test ping returns HTTP 200 with signature validation.
- [ ] Admin metabox displays Gateway, Transaction ID, UTR, and AWB fields.
- [ ] Track Order page `[axdoro_track_order]` renders order progress timeline when valid Order ID and contact details are submitted.

### Support & Security
- [ ] Floating AI/FAQ Chatbot opens and responds to queries (sizing, 240 GSM fabric, shipping, returns).
- [ ] "Talk to Human on WhatsApp" handoff opens chat with client support desk.
- [ ] SSL padlock green and active on all URLs.
- [ ] UpdraftPlus automated backup scheduled.

---

## 8. Admin Handoff & Role Matrix

| User Role | Credentials / Permissions | Permitted Activities |
| :--- | :--- | :--- |
| **Administrator** | PeoplePoint & Client Lead | Full system configuration, gateway API keys, webhooks, plugins, theme options. |
| **Shop Manager** | AXDORO Operations Staff | Add/edit products, manage stock, view orders, update order status (Packed/Shipped), input AWB tracking numbers, verify UTR payments. |
| **Marketing** | AXDORO Social Media Lead | Edit homepage banners, announcement messages, and blog/editorial content. |
