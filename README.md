# NOW Campaign Storefronts for WooCommerce

[繁體中文 README](README.zh-TW.md) ·
[WordPress.org](https://wordpress.org/plugins/now-campaign-storefronts/) ·
[GitHub](https://github.com/bboyfan/now-campaign-storefronts)

> Group buy pages, mix-and-match bulk discounts, and password-protected influencer sales reports for WooCommerce — without duplicating products or inventory.

NOW Campaign Storefronts is an independent open-source project and is not an official WooCommerce or Automattic product.

## Who Is It For?

- **Influencer / KOL collaborations** — Give each influencer a dedicated storefront URL and a password-protected live sales report (zero customer PII exposure).
- **Group buy and flash sale campaigns** — Create time-limited campaign pages with special pricing, without touching your main catalog.
- **Mix-and-match bulk promotions** — "Buy any 3 items, get 5% off; buy 5, get 10% off" across all campaign products.
- **Private or VIP storefronts** — Exclusive campaign URLs with campaign-only pricing for specific audiences.

## Features

- Campaign-specific pricing for any WooCommerce product or variation — your catalog prices stay unchanged.
- Mix-and-match bulk quantity discounts based on total campaign items purchased across products and variations.
- Quick Order, Editorial, and Compact storefront layouts, fully responsive for desktop and mobile.
- Campaign sections, image galleries, rich content, color customization, and WordPress shortcode support.
- WooCommerce cart and session integration with Classic Checkout compatibility.
- Automatic sales tracking per campaign on every WooCommerce order (HPOS compatible).
- Live campaign reports with automatic refund deductions.
- Password-protected sales reports shareable with influencers — no customer names, emails, or addresses exposed.
- One-click campaign duplication with all sections, products, pricing, and styling preserved.
- Native Bricks Builder integration: Custom Single Templates, Query Loops, Dynamic Data Tags, and campaign display conditions.
- Theme-aware presentation with isolated campaign commerce controls (quantity pickers, add-to-cart, floating mini cart).

Campaign Bulk Pricing is a quantity-dependent Campaign Price, not a second discount engine. Eligible products and variations in the same Campaign are counted together; the highest reached tier adjusts each item from its own Campaign Price before WooCommerce coupons or compatible dynamic-pricing rules continue through the normal WooCommerce flow.

## Screenshots

### Campaign Storefront
![Campaign Storefront — group buy page with product cards, quantity pickers, bulk discount badges, and floating mini cart](docs/screenshots/campaign-storefront.png)

### Campaign Editor
![Campaign Editor — drag-and-drop sections, WooCommerce product picker, campaign pricing, and layout options](docs/screenshots/campaign-editor.png)

### Campaign Bulk Pricing
![Bulk Pricing — mix-and-match discount setup: buy 3 get 5% off, buy 5 get 10% off](docs/screenshots/bulk-pricing.png)

### Live Campaign Report
![Live Report — password-protected sales report for influencer collaboration with revenue, orders, refunds, and product breakdown](docs/screenshots/live-report.png)

## Requirements

- WordPress 6.5 or newer.
- WooCommerce 8.0 or newer.
- PHP 8.1 or newer.

The current public release candidate is tested with WordPress 7.0 and WooCommerce 10.9.

## Architecture

NOW Campaign Storefronts deliberately keeps WooCommerce authoritative for commerce data:

```text
WooCommerce Product / Variation = Product authority
WooCommerce Inventory           = Inventory authority
WooCommerce Cart / Session      = Cart authority
WooCommerce Coupon / Pricing    = Discount authority
WooCommerce Order / Refund      = Financial authority

NOW Campaign Storefronts = campaign context + campaign price + attribution + reporting + presentation
```

## Installation

1. Install and activate WooCommerce.
2. Install and activate NOW Campaign Storefronts for WooCommerce from [WordPress.org](https://wordpress.org/plugins/now-campaign-storefronts/) or upload the plugin ZIP.
3. Open the Campaigns screen in WordPress admin.
4. Create a campaign and add products or variations.
5. Set Campaign Prices and, optionally, Campaign Bulk Pricing quantity tiers.
6. Configure content and presentation, then publish.

## Development

The plugin uses a PSR-4-style `Bboyfan\NowCampaignStorefronts\` namespace and includes a runtime fallback autoloader. `composer.json` is included so the source can be inspected and worked on with Composer-based tooling.

Before submitting changes, run PHP syntax checks and JavaScript syntax checks and verify Campaign Price, Bulk Pricing, attribution, reporting, refunds, and storefront behavior against WooCommerce.

The CSS distributed with the plugin is the project's source CSS; it is not generated from a separate proprietary stylesheet source.

## WordPress.org release

The private development source keeps its existing internal identifiers. `scripts/build-wordpress-org.sh` creates the WordPress.org-ready package using the public identity `NOW Campaign Storefronts for WooCommerce`, slug `now-campaign-storefronts`, main file `now-campaign-storefronts.php`, and text domain `now-campaign-storefronts` without changing runtime class names, database identifiers, CSS classes, or option/meta prefixes.

The WordPress.org package uses English canonical source strings. WordPress.org supplies translation language packs; no translation binaries are bundled in the ZIP.

## Security

Please do not report security vulnerabilities in a public issue. See [SECURITY.md](SECURITY.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
