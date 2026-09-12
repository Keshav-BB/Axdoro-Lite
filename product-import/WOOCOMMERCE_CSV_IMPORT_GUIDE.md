# AXDORO WooCommerce Product Import Guide (200+ Products)

This guide provides step-by-step instructions for importing AXDORO's full 200+ product catalogue into WooCommerce using the native WooCommerce CSV Importer without manual entry or paid plugins.

---

## 1. Catalogue Data Structure

To deliver a Myntra-style shopping experience where customers can select sizes (XS–XXL) and colours on a single product page, AXDORO products are imported as **Variable Products**.

### Parent vs. Child Row Structure

| Row Type | `Type` Column | `SKU` Column | `Parent` Column | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| **Parent Product** | `variable` | e.g. `AXD-OVR-BLK-001` | *(leave empty)* | Defines the main product page, title, categories, tags, main hero image, and all available sizes/colors. |
| **Child Variation** | `variation` | e.g. `AXD-OVR-BLK-001-M` | `AXD-OVR-BLK-001` | Defines individual stock and pricing for each size (e.g. Size M, Size L). |

---

## 2. Required Spreadsheet Columns

When compiling AXDORO's source-of-truth spreadsheet, ensure the following columns are present:

1. **Type**: `variable` for parent style; `variation` for size/color combinations.
2. **SKU**: Unique identifier for every single row. Format recommendation: `AXD-[CATEGORY]-[COLOR]-[NUMBER]-[SIZE]`.
3. **Name**: Full product name (e.g., `AXDORO 240 GSM Oversized Heavyweight Tee - Jet Black`).
4. **Published**: `1` (Live) or `0` (Draft).
5. **Is featured?**: `1` (Shows on Homepage Featured/Trending) or `0`.
6. **Visibility in catalog**: `visible`.
7. **Short description**: 2–3 line summary highlighting 240 GSM fabric, fit, and key look.
8. **Description**: Complete product details, care instructions, and sizing notes.
9. **In stock?**: `1` for in stock, `0` for out of stock.
10. **Stock**: Specific stock quantity per variation (e.g. 25).
11. **Regular price**: Regular retail price (e.g. `1299`).
12. **Sale price**: Discounted launch price (e.g. `999`).
13. **Categories**: Comma-separated categories (e.g. `Oversized, New Arrivals, Best Sellers`).
14. **Tags**: Search tags (e.g. `streetwear, 240 gsm, oversized, black`).
15. **Images**: Direct URL to image. Multiple images separated by comma.
16. **Parent**: Only required for `variation` rows; must match the Parent product's exact `SKU`.
17. **Attribute 1 name**: `Size`
18. **Attribute 1 value(s)**: For Parent: `XS, S, M, L, XL, XXL`. For Variation: specific size like `M`.
19. **Attribute 1 visible**: `1` for Parent, `0` for Variation.
20. **Attribute 1 global**: `1`
21. **Attribute 2 name**: `Color`
22. **Attribute 2 value(s)**: e.g. `Jet Black`.
23. **Attribute 3 name**: `Fabric`
24. **Attribute 3 value(s)**: `240 GSM Cotton`.

---

## 3. Step-by-Step Import Process in WordPress

1. Log into WordPress Admin (`/wp-admin`).
2. Navigate to **Products > All Products**.
3. Click the **Import** button at the top of the screen.
4. Under **Choose a CSV file from your computer**, select `axdoro_sample_products_import.csv` (or the final client CSV).
5. If updating existing products by SKU, check the box: *"Existing products that match by ID or SKU will be updated"*.
6. Click **Continue**.
7. **Column Mapping Screen**:
   - WooCommerce will automatically map standard headers.
   - Verify that `Type`, `SKU`, `Name`, `Price`, `Attributes`, and `Parent` are accurately mapped.
8. Click **Run the Importer**.
9. The importer will process in batches of 10–20 products. For 200+ products, import will complete in 2–4 minutes.

---

## 4. Handling Product Photography

- **Recommended Aspect Ratio**: 4:5 vertical fashion crop (e.g., `1200 x 1500 px` or `1000 x 1250 px`).
- **File Naming Convention**: Use clean, SKU-matched names:
  - `AXD-OVR-BLK-001-front.webp`
  - `AXD-OVR-BLK-001-back.webp`
  - `AXD-OVR-BLK-001-detail.webp`
- **Batch Upload Option**:
  1. Upload all product images in bulk to **Media > Add New**.
  2. Copy image URLs into the spreadsheet `Images` column before running the import.
  3. WooCommerce will link them automatically to each product gallery!

---

## 5. Pre-Go-Live Validation Checklist

Before presenting the imported catalogue to the client:
- [ ] Ensure all `[SAMPLE]` products are deleted or placed into `Draft` status.
- [ ] Confirm no two products share the same SKU.
- [ ] Confirm each variable product displays working size and color dropdowns.
- [ ] Test adding each size to Bag to verify variation stock deduction.
- [ ] Verify category archives (`/product-category/oversized/`, `/product-category/half-sleeve/`) display the correct items.
