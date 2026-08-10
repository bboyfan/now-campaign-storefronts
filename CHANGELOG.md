# Changelog

All notable changes to WC Campaign are documented here.

## 1.2.0 — 2026-08-10

Campaign storefront consistency and campaign-wide bulk pricing release.

### Added

- Campaign Bulk Pricing with campaign-wide mix-and-match quantity tiers.
- Bulk tiers count eligible products and variations from the same Campaign together.
- Each tier applies a percentage reduction to each item's own Campaign Price, keeping different product price points intact.
- Campaign-specific storefront heading and description controls for active Bulk Pricing offers.
- Optional Campaign page-title visibility setting; the WordPress document title remains unchanged when the visible H1 is hidden.
- Section-level Campaign product copy color control.

### Changed

- Variable Campaign items include their variation attributes directly in the visible product title across Quick Order, Editorial, and Compact Grid.
- Campaign product copy is standardized at 14px.
- Section color controls use the same Set / Not set state labels as Campaign-level color controls.
- Standardized Add to cart copy and CTA presentation across all three product layouts.
- All three layouts share the same CTA hover behavior, derived from the configured CTA background with a slight brightness increase.
- Adding Campaign products refreshes Mini Cart data without automatically expanding the Mini Cart panel.
- Mini Cart keeps Checkout as its only navigation action; View cart is no longer shown.
- Product imagery across the three Campaign product layouts uses centered `contain` behavior to preserve image proportions.
- Campaign Bulk Pricing is resolved before later WooCommerce coupon / compatible dynamic-pricing processing, so WooCommerce remains the discount authority.
- Order attribution stores the effective Campaign Price after the reached bulk tier, while WooCommerce order totals remain the financial authority.
- Bulk Pricing percentage inputs accept both whole-number and decimal percentages.

### Fixed

- Section product-copy color overrides persist after Campaign saves, including newly created sections.
- Restored localized Mini Cart Checkout copy for the Traditional Chinese interface.
- Fixed WordPress.org 1.2.0 i18n preparation by removing obsolete translation msgids and ensuring generated English canonical strings remain valid PHP source.

### Compatibility / architecture

- WooCommerce Product / Variation remains the product authority.
- WooCommerce inventory remains the inventory authority.
- WooCommerce Cart / Session remains the cart authority.
- WooCommerce / compatible pricing rules / Coupon remains the discount authority.
- WooCommerce Order / HPOS / Refund remains the financial authority.
- WC Campaign remains responsible only for Campaign context, Campaign Price, attribution, reporting, and presentation.
- Campaign Bulk Pricing is a quantity-dependent Campaign Price, not a parallel discount engine.

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

- External report authentication uses WordPress Core password protection (`post_password` and `wp-postpass_*`) instead of a custom report session cookie.
- External report passwords are backed by an internal, non-public WordPress password record while preserving the public `/campaign-report/{share-key}/` URL format.
- Existing recoverable credentials from pre-release builds are migrated to the WordPress-native password authority when available.
- Campaign report pages and data endpoints emit explicit no-cache and no-index headers.
- The Campaign editor and storefront presentation layer were refined with clearer publish controls, rich campaign introductions, section-based layouts, and theme-aware styling.
- WordPress.org packaging uses the `wc-campaign` slug/text domain, English canonical source strings, and bundled `zh_TW` translations.

### Fixed

- Campaign introduction content passes through the standard `the_content` filter so registered shortcodes render correctly.
- Stabilized the Campaign rich-text editor and moved Preview / Save controls into the publish settings card.
- Hardened quantity and Add to Cart controls against broad theme or page-builder CSS overrides.
- Fixed the WordPress.org release builder so the main plugin file always preserves the required `<?php` opening tag.
- Added a release guard and PHP syntax checks to prevent invalid plugin packages from being generated.
- Removed the obsolete custom external-report authentication runtime after moving to WordPress-native password protection.

### Architecture

- WooCommerce Product / Variation remains the product authority.
- WooCommerce inventory remains the inventory authority.
- WooCommerce Cart / Session remains the cart authority.
- WooCommerce Coupon / Pricing remains the discount authority.
- WooCommerce Order / HPOS / Refund remains the financial authority.
- WC Campaign is responsible for campaign context, campaign price, attribution, reporting, and presentation.
