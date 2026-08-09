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

## Attribution

Campaign identity and campaign snapshots are stored on WooCommerce order items so reporting remains tied to WooCommerce orders and refunds.

## Reporting

Campaign reporting derives sales and refund-aware metrics from WooCommerce order/order-item data. WC Campaign does not maintain an independent financial ledger.

## Storefront

WC Campaign provides campaign layouts, sections, content, quantity controls, Add to Cart UI, and a bottom mini cart. General typography/theme presentation can inherit from the active theme while commerce controls are isolated from aggressive global theme styles.
