# Architecture

WC Campaign adds campaign context to WooCommerce without creating a parallel commerce system.

## Authority model

```text
WooCommerce Product / Variation = Product authority
WooCommerce Inventory           = Inventory authority
WooCommerce Cart / Session      = Cart authority
WooCommerce Coupon / Pricing    = Discount authority
WooCommerce Order / Refund      = Financial authority

WC Campaign = campaign context + campaign price + attribution + reporting + presentation
```

## Campaign products

A simple WooCommerce product can be referenced as a campaign item. For variable products, each purchasable variation is represented independently so inventory, attributes, and pricing remain attached to the WooCommerce variation.

## Pricing

Campaign Price is applied inside the WooCommerce cart flow. WC Campaign does not replace WooCommerce coupons or the order totals ledger.

Campaign Bulk Pricing extends Campaign Price with campaign-wide quantity tiers. Eligible Campaign products and variations are counted together. The highest reached tier adjusts each eligible line from that line's own Campaign Price, producing an effective Campaign Price before later WooCommerce coupon or compatible dynamic-pricing processing.

```text
WooCommerce product price
        ↓
Campaign Price
        ↓
Campaign Bulk Pricing tier
        ↓
Effective Campaign Price
        ↓
WooCommerce coupon / compatible pricing rules
        ↓
WooCommerce cart / checkout / order totals
```

Bulk Pricing therefore does not create a second discount engine, fee system, coupon system, cart, or financial ledger.

## Attribution

Campaign identity and campaign snapshots are stored on WooCommerce order items so reporting remains tied to WooCommerce orders and refunds. When a Bulk Pricing tier is active, attribution stores the effective Campaign Price reached before later WooCommerce discounts.

## Reporting

Campaign reporting derives sales and refund-aware metrics from WooCommerce order/order-item data. WC Campaign does not maintain an independent financial ledger.

## Storefront

WC Campaign provides campaign layouts, sections, content, quantity controls, Add to Cart UI, Campaign Bulk Pricing messaging, and a bottom mini cart. General typography/theme presentation can inherit from the active theme while commerce controls are isolated from aggressive global theme styles.
