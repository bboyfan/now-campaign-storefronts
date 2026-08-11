# Changelog

All notable changes to Woo Campaign / WC Campaign are documented here.

## 1.4.1 — 2026-08-11

Bricks integration hardening.

### Fixed

- Bricks element conditions: `campaign_current` is now evaluated by WC
  Campaign as the final authority (late filter priority), so no extension
  fallback can override the result. Campaign resolution prefers the Bricks
  page context (`preview_or_post_id`) and always validates through
  `CampaignContext`; outside a Campaign page every condition evaluates to
  false regardless of `is` / `is not`.
- Dynamic data now renders inside mixed text content (e.g.
  `開團：{wc_campaign_title}`) in Text, Heading, Rich Text, and Button
  elements. The tags are registered as a real Bricks 2.3.10 provider through
  `bricks/dynamic_data/register_providers`, using Bricks' native tag
  parsing pipeline instead of the single-tag `render_tag` path only.
- Campaign image dynamic data follows the Bricks image contract: attachment
  ID for the Image element (image context), URL string for text/link
  contexts.
- Campaign Products query loop id is scoped to the `campaign_products` query
  type only, so other Bricks queries are never rewritten.

### Changed

- Removed the no-op `bricks/query/loop_object` registration for Campaign
  Products loops.
- `bricks/active_templates` capture now accepts the full 3-argument Bricks
  contract and only flips content ownership on content-template passes
  (header/footer/archive passes can no longer affect it).
- Corrected the `template_include` comment in the native renderer: page
  ownership is captured during the Bricks `wp` lifecycle, not by running
  after Bricks on `template_include`.

## 1.4.0 — 2026-08-11

Bricks Builder integration release.

### Added

- Native Bricks Builder integration: `woo_campaign` is supported in the
  Bricks Builder and can be designed with Bricks Single Templates.
- Bricks template ownership: when a Bricks content template is assigned to a
  Campaign, the native `single-woo_campaign.php` template is not forced and
  `the_content` / `wp_footer` no longer auto-inject the product list or Mini
  Cart, so Bricks owns the page layout.
- Campaign Products Query Loop for Bricks, returning `CampaignProduct` domain
  objects so each loop item keeps campaign, section, product, and variation
  identity.
- WC Campaign dynamic data tags (Campaign and Campaign Product): ID, title,
  slug, excerpt, featured image, product name, variation, image, Woo reference
  price, Campaign price, savings, copy, stock note, and source product /
  variation IDs.
- Campaign-aware Bricks element conditions: shared elements can be shown or
  hidden per current Campaign (`is` / `is not`, multi-select).
- Shared `CampaignContext` and `CampaignProductPresentationResolver` so native
  storefront rendering and Bricks dynamic data always produce identical
  values.

### Compatibility / architecture

- Bricks remains an optional dependency: without Bricks (or without an
  assigned Campaign template) the native storefront is completely unchanged.
- `[woo_campaign_products]` can still be placed inside a Bricks template and
  loads the full native purchase UI, Campaign pricing, bulk pricing, cart, and
  attribution unchanged.
- Commerce authority is untouched: WooCommerce remains authoritative for
  products, inventory, carts, discounts, orders, and refunds; this release
  only adds presentation primitives.

## 1.3.0 — 2026-08-10

Campaign duplication release.

### Added

- Duplicate Campaign action from the Campaign list.
- Duplicated campaigns preserve Campaign configuration, sections, products,
  variation references, campaign pricing, bulk pricing and presentation while
  receiving new internal identities.

### Compatibility / architecture

- Duplicated Campaigns are created as drafts with a unique URL slug and never
  share the source external report key or hidden password record.
- WooCommerce Product / Variation remains the product authority; duplication
  never creates second products, inventory, carts, discount engines, or
  financial ledgers. A duplicated Campaign starts with zero sales, orders, and
  units, and the source Campaign is never modified.

## 1.2.0 — 2026-08-10

Campaign storefront consistency and campaign-wide bulk pricing release.

### Added

- Campaign Bulk Pricing with campaign-wide mix-and-match quantity tiers.
- Bulk tiers count eligible products and variations from the same Campaign together.
- Each tier applies a percentage reduction to each item's own Campaign Price, keeping different product price points intact.
- Storefront messaging for active Campaign Bulk Pricing tiers.
- Campaign Bulk Pricing storefront title and description can be customized per Campaign from the editor.
- Optional Campaign page-title visibility setting; the WordPress document title remains unchanged when the visible H1 is hidden.
- Section-level Campaign product copy color control.

### Changed

- Variable Campaign items include their variation attributes directly in the visible product title across Quick Order, Editorial, and Compact Grid.
- Campaign product copy is standardized at 14px.
- Section color controls use the same Set / Not set state labels as Campaign-level color controls.
- Standardized Add to cart copy, CTA sizing, colors, borders, and hover behavior across all three product layouts.
- CTA hover uses the configured CTA color with a subtle brightness increase instead of layout-specific hover styles.
- Adding Campaign products refreshes Mini Cart data without automatically expanding the Mini Cart panel.
- Mini Cart keeps Proceed to checkout as its only navigation action; View cart is no longer shown.
- Product imagery across the three Campaign product layouts uses centered `contain` behavior to preserve image proportions.
- Campaign Bulk Pricing editor spacing now follows the standard Campaign editor card padding and field layout.
- Campaign Bulk Pricing is resolved before later WooCommerce coupon / WDP processing, so WooCommerce remains the discount authority.
- Order attribution stores the effective Campaign Price after the reached bulk tier, while WooCommerce order totals remain the financial authority.

### Fixed

- Fixed variable-product headings so selected variation attributes remain visible, for example `淨味噴霧 - 冷水`.
- Fixed CTA fallback colors so leaving section CTA colors unset cannot produce an unreadable white-on-white button.
- Section product-copy color overrides now persist reliably after Campaign saves, including newly created sections.
- Campaign Bulk Pricing discount inputs now accept whole-number percentages such as `5` and `10` while still allowing decimal percentages.
- Restored the Traditional Chinese `前往結帳` label in the private zh-TW-first Mini Cart UI.
- Removed obsolete WordPress.org translation msgids from the prepared 1.2.0 localization map so strict i18n validation remains exact.
- Adjusted a build-time English canonical replacement so the generated public PHP source remains syntactically valid.

### Compatibility / architecture

- WooCommerce Product / Variation remains the product authority.
- WooCommerce inventory remains the inventory authority.
- WooCommerce Cart / Session remains the cart authority.
- WooCommerce / WDP / Coupon remains the discount authority.
- WooCommerce Order / HPOS / Refund remains the financial authority.
- WC Campaign remains responsible only for Campaign context, Campaign Price, attribution, reporting, and presentation.

## 1.1.1 — 2026-08-10

First public release candidate prepared for WordPress.org.

### Added

- Campaign pricing for WooCommerce simple products and purchasable variations.
- Quick Order, Editorial, and Compact campaign storefront layouts.
- Campaign section builder with section-level imagery, copy, product layout, and product selection.
- Campaign media gallery, rich campaign introduction content, and shortcode rendering through the standard WordPress content pipeline.
- Campaign-aware cart/session flow while keeping WooCommerce authoritative for products, inventory, cart, checkout, orders, refunds, and financial data.
- HPOS-compatible order-item campaign attribution.
- Refund-aware campaign reporting with campaign subtotal, discounts, refunds, net sales, paid orders, units, pending orders, average order value, refunded units, and product-level performance.
- Password-protected external live reports with share-link regeneration and 15-second data refresh.
- Traditional Chinese (`zh_TW`) localization for the WordPress.org package.

### Changed

- Rebuilt the Campaign editor and storefront presentation layer with clearer publish controls, campaign introduction editing, section-based presentation, and theme-aware styling.
- Moved the external report authentication layer to WordPress Core password protection using `post_password` / `wp-postpass_*` instead of a custom report session cookie.
- External report passwords now use an internal, non-public WordPress password record while preserving the existing `/campaign-report/{share-key}/` URL format and report dashboard.
- Existing recoverable report credentials are migrated lazily to the WordPress-native password authority when possible.
- Campaign report pages and data endpoints emit explicit no-cache / no-index response headers while relying on the WordPress-native password cookie for authenticated access.
- WordPress.org release builds use the public `wc-campaign` slug, `wc-campaign` text domain, English canonical strings, and bundled `zh_TW` translations while preserving private runtime identifiers needed for upgrade compatibility.

### Fixed

- Campaign introduction content now passes through the standard `the_content` filter so registered shortcodes render correctly.
- Stabilized the Campaign editor rich-text field and moved Preview / Save controls into the publish settings card without making the card sticky.
- Hardened Campaign quantity and Add to Cart controls against broad theme / builder CSS overrides.
- Fixed the WordPress.org build pipeline so `wc-campaign.php` always preserves the required `<?php` opening tag.
- Added a hard release guard so a missing PHP opening tag fails the build before packaging.
- Added PHP syntax validation to the WordPress.org release builder.
- Removed the custom external-report authentication class after the migration to WordPress-native password protection.

### Compatibility / architecture

- WooCommerce Product / Variation remains the product authority.
- WooCommerce inventory remains the inventory authority.
- WooCommerce Cart / Session remains the cart authority.
- WooCommerce / WDP / Coupon remains the discount authority.
- WooCommerce Order / HPOS / Refund remains the financial authority.
- WC Campaign remains responsible only for Campaign context, Campaign Price, attribution, reporting, and presentation.
