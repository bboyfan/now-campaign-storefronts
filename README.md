# WC Campaign

[繁體中文](README.zh-TW.md)

WC Campaign is a free and open-source WooCommerce extension for campaign-specific storefronts, campaign pricing, campaign-wide quantity pricing, order attribution, and live reporting.

It is designed for group buys, influencer campaigns, limited-time sales, private campaign pages, and other campaign-specific storefronts while keeping WooCommerce as the source of truth for products, inventory, carts, orders, refunds, coupons, and financial data.

> **Project status:** WC Campaign 1.3.0 is the current public source and WordPress.org review package. The plugin-directory submission is still awaiting review.

## Features

- Campaign pricing for WooCommerce simple products and purchasable variations.
- Duplicate any Campaign from the Campaign list, preserving configuration, sections, products, pricing, bulk pricing, and presentation with fresh internal identities.
- Campaign Bulk Pricing with campaign-wide mix-and-match quantity tiers.
- Bulk tiers use each eligible item's own Campaign Price as the pricing baseline.
- Optional custom storefront heading and description for the bulk-pricing offer.
- Quick Order, Editorial, and Compact campaign layouts.
- Variation attributes included in visible campaign product titles.
- Campaign sections, image galleries, rich content, and shortcode support.
- Optional visible Campaign page title.
- Section-level product-copy and CTA color controls.
- Unified Add to cart CTA presentation and hover behavior across all three layouts.
- Product imagery uses centered `contain` behavior to preserve proportions.
- WooCommerce cart/session integration and Classic Checkout compatibility.
- Bottom Mini Cart with quantity editing and a single Checkout action.
- Order-item campaign attribution with HPOS support.
- Refund-aware campaign reporting.
- Password-protected live report links with product-level performance metrics.
- WordPress-native report password sessions using Core `post_password` / `wp-postpass_*` behavior.
- Theme-aware presentation with isolated campaign commerce controls.
- Traditional Chinese (`zh_TW`) localization.

## Requirements

- WordPress 6.5 or newer.
- WooCommerce 8.0 or newer.
- PHP 8.1 or newer.

The current release has been tested with WordPress 7.0 and WooCommerce 10.9.

## Architecture

WC Campaign deliberately keeps WooCommerce authoritative for commerce data:

```text
WooCommerce Product / Variation = Product authority
WooCommerce Inventory           = Inventory authority
WooCommerce Cart / Session      = Cart authority
WooCommerce Coupon / Pricing    = Discount authority
WooCommerce Order / Refund      = Financial authority

WC Campaign = campaign context + campaign price + attribution + reporting + presentation
```

Campaign Bulk Pricing is implemented as a quantity-dependent Campaign Price. It does not create a second discount engine: eligible products and variations in the same Campaign are counted together, the reached tier adjusts each line from its own Campaign Price, and WooCommerce coupons / compatible pricing rules continue afterward through the normal WooCommerce flow.

WC Campaign does not create a second product catalog, inventory system, cart, checkout, order ledger, refund ledger, or financial counter.

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for more detail.

## Installation

### Download the installable ZIP

Download the packaged plugin archive here:

**[WC Campaign 1.3.0 — wc-campaign-1.3.0.zip](releases/wc-campaign-1.3.0.zip?raw=1)**

Then in WordPress go to **Plugins → Add Plugin → Upload Plugin**, upload the ZIP, and activate **WC Campaign**. WooCommerce must already be installed and active.

The SHA256 checksum is available at [`releases/wc-campaign-1.3.0.sha256`](releases/wc-campaign-1.3.0.sha256).

### WordPress.org

WC Campaign has been submitted to WordPress.org and is currently awaiting review. Once approved and published, it will also be installable directly from the WordPress plugin installer.

## Campaign Bulk Pricing

Bulk Pricing can be enabled per Campaign. Tiers are based on the total eligible quantity across products and variations in that Campaign, which supports mix-and-match group-buy offers such as 2+, 4+, and 8+ item discounts.

Each product keeps its own Campaign Price. For example, a 10% tier changes Campaign Prices of 500, 400, and 550 to 450, 360, and 495 respectively. WooCommerce remains responsible for coupons, checkout totals, orders, and refunds.

The storefront offer heading and description can be customized per Campaign while tier badges are generated from the configured quantity rules.

## Campaign duplication

Every Campaign row in the Campaign list has a **Duplicate** action. Duplicating creates a new draft Campaign with the same configuration, sections, products and variation references, Campaign pricing, bulk pricing, and presentation. The new Campaign receives its own post, section, and product-relationship IDs plus a fresh external-report share key and password record, so it never shares the source Campaign's public report link or financial attribution.

## Campaign introduction and shortcodes

Campaign introduction content uses the standard WordPress `the_content` pipeline. Registered shortcodes, including template shortcodes supplied by themes or other plugins, can therefore render inside campaign content.

## Live reports

Campaign owners can optionally enable a password-protected external report. The public report keeps the `/campaign-report/{share-key}/` format, while authentication is delegated to WordPress Core password protection. WordPress therefore owns the password session through its standard `wp-postpass_*` cookie rather than WC Campaign maintaining a separate login/session system.

The report shows aggregate campaign metrics and product-level performance without exposing customer contact details or order numbers.

See [docs/PRIVACY.md](docs/PRIVACY.md).

## Localization

English is the canonical source language. Traditional Chinese (`zh_TW`) translations are included in `languages/`.

## Development

The plugin uses the `WooCampaign\\` PHP namespace and includes a runtime fallback autoloader. `composer.json` is included for source inspection and Composer-based tooling.

Before submitting a pull request, run PHP syntax checks and JavaScript syntax checks and verify the commerce behavior relevant to your change.

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history and notable implementation changes.

## Security

Please do **not** report suspected security vulnerabilities in public issues. Follow [SECURITY.md](SECURITY.md).

## Open source

WC Campaign is free software released under the **GNU General Public License v2.0 or later (GPL-2.0-or-later)**. You may use, study, modify, and redistribute it under the terms of that license.

See [OPEN_SOURCE.md](OPEN_SOURCE.md) and [LICENSE](LICENSE).

WC Campaign is an independent open-source project and is not an official WooCommerce or Automattic product. WooCommerce is a trademark of Automattic Inc.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) before participating.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
