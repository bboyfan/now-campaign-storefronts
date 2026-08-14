# Architecture

NOW Campaign Storefronts adds campaign context to WooCommerce without creating a parallel commerce system.

## Authority model

```text
WooCommerce Product / Variation = Product authority
WooCommerce Inventory           = Inventory authority
WooCommerce Cart / Session      = Cart authority
WooCommerce Coupon / Pricing    = Discount authority
WooCommerce Order / Refund      = Financial authority

NOW Campaign Storefronts = campaign context + campaign price + attribution + reporting + presentation
```

## Campaign products

A simple WooCommerce product can be referenced as a campaign item. For variable products, each purchasable variation is represented independently so inventory, attributes, and pricing remain attached to the WooCommerce variation.

## Campaign duplication

The Campaign list exposes a Duplicate action per Campaign row. Duplication copies the complete user configuration — Campaign meta, sections, product/variation references, campaign prices, bulk pricing, presentation, and external-report intent — into a new draft Campaign while regenerating every unique identity.

New identities are created for the WordPress post, the Campaign section rows, the Campaign product-relationship rows, the external report share key, and the hidden WordPress report-password record. The source Campaign is never modified, and WooCommerce products, inventory, orders, refunds, and financial data are never duplicated. A duplicated Campaign therefore starts with zero sales, orders, and units.

## Pricing

Campaign Price is applied inside the WooCommerce cart flow. NOW Campaign Storefronts does not replace WooCommerce coupons or the order totals ledger.

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

Campaign reporting derives sales and refund-aware metrics from WooCommerce order/order-item data. NOW Campaign Storefronts does not maintain an independent financial ledger.

## Storefront

NOW Campaign Storefronts provides campaign layouts, sections, content, quantity controls, Add to Cart UI, Campaign Bulk Pricing messaging, and a bottom mini cart. General typography/theme presentation can inherit from the active theme while commerce controls are isolated from aggressive global theme styles.
