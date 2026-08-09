# WC Campaign

[繁體中文](README.zh-TW.md)

WC Campaign is a free and open-source WooCommerce extension for campaign-specific storefronts, campaign pricing, order attribution, and live reporting.

It is designed for group buys, influencer campaigns, limited-time sales, private campaign pages, and other campaign-specific storefronts while keeping WooCommerce as the source of truth for products, inventory, carts, orders, refunds, coupons, and financial data.

> **Project status:** WC Campaign 1.1.1 has been submitted to the WordPress.org Plugin Directory and is awaiting review.

## Features

- Campaign pricing for WooCommerce simple products and purchasable variations.
- Quick Order, Editorial, and Compact campaign layouts.
- Campaign sections, image galleries, rich content, and shortcode support.
- WooCommerce cart/session integration and Classic Checkout compatibility.
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

WC Campaign does not create a second product catalog, inventory system, cart, checkout, order ledger, or refund ledger.

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for more detail.

## Installation

### Manual installation

1. Download the latest release ZIP.
2. In WordPress, go to **Plugins → Add Plugin → Upload Plugin**.
3. Upload the ZIP and activate **WC Campaign**.
4. Make sure WooCommerce is installed and active.
5. Open **Campaigns** in the WordPress admin and create your first campaign.

### WordPress.org

WC Campaign has been submitted to WordPress.org and is currently awaiting review. Once approved and published, it will also be installable directly from the WordPress plugin installer.

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
