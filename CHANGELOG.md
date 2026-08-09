# Changelog

All notable changes to WC Campaign are documented here.

## 1.1.1 — 2026-08-10

First public release candidate prepared for WordPress.org.

### Added

- Campaign pricing for WooCommerce simple products and purchasable variations.
- Quick Order, Editorial, and Compact campaign storefront layouts.
- Campaign section builder with section-level imagery, copy, product layout, and product selection.
- Campaign media gallery, rich campaign introduction content, and shortcode rendering through the standard WordPress content pipeline.
- WooCommerce cart/session integration while keeping WooCommerce authoritative for products, inventory, checkout, orders, refunds, coupons, and financial data.
- HPOS-compatible order-item campaign attribution.
- Refund-aware campaign reporting with campaign subtotal, discounts, refunds, net sales, paid orders, units, pending orders, average order value, refunded units, and product-level performance.
- Password-protected external live reports with share-link regeneration and 15-second data refresh.
- Traditional Chinese (`zh_TW`) localization.

### Changed

- External report authentication now uses WordPress Core password protection (`post_password` and `wp-postpass_*`) instead of a custom report session cookie.
- External report passwords are backed by an internal, non-public WordPress password record while preserving the public `/campaign-report/{share-key}/` URL format.
- Existing recoverable credentials from pre-release builds are migrated to the WordPress-native password authority when available.
- Campaign report pages and data endpoints emit explicit no-cache and no-index headers.
- The Campaign editor and storefront presentation layer were refined with clearer publish controls, rich campaign introductions, section-based layouts, and theme-aware styling.
- WordPress.org packaging uses the `wc-campaign` slug/text domain, English canonical source strings, and bundled `zh_TW` translations.

### Fixed

- Campaign introduction content now passes through the standard `the_content` filter so registered shortcodes render correctly.
- Stabilized the Campaign rich-text editor and moved Preview / Save controls into the publish settings card.
- Hardened quantity and Add to Cart controls against broad theme or page-builder CSS overrides.
- Fixed the WordPress.org release builder so the main plugin file always preserves the required `<?php` opening tag.
- Added a release guard and PHP syntax checks to prevent invalid plugin packages from being generated.
- Removed the obsolete custom external-report authentication runtime after moving to WordPress-native password protection.

### Architecture

- WooCommerce Product / Variation remains the product authority.
- WooCommerce inventory remains the inventory authority.
- WooCommerce Cart / Session remains the cart authority.
- WooCommerce / pricing rules / coupons remain the discount authority.
- WooCommerce Order / HPOS / Refund remains the financial authority.
- WC Campaign is responsible for campaign context, campaign price, attribution, reporting, and presentation.
