=== NOW Campaign Storefronts for WooCommerce ===
Contributors: bboyfan
Tags: woocommerce, campaigns, group buying, reporting, storefront
Requires at least: 6.5
Tested up to: 7.0
Stable tag: 1.4.3
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build campaign storefronts for WooCommerce with campaign pricing, layouts, attribution, live reports, and protected sharing.

== Description ==

NOW Campaign Storefronts for WooCommerce adds campaign-specific storefronts and reporting while keeping WooCommerce as the source of truth for products, inventory, carts, orders, refunds, and discounts.

NOW Campaign Storefronts is an independent open-source project and is not an official WooCommerce or Automattic product.

It is designed for stores that run group buys, influencer campaigns, limited-time sales, private campaign pages, or other campaign-specific storefronts without duplicating WooCommerce product or order data.

Features include:

* Campaign-specific pricing for WooCommerce simple products and variations.
* Duplicate any Campaign from the Campaign list, preserving configuration, sections, products, pricing, bulk pricing, and presentation with fresh internal identities.
* Campaign-wide mix-and-match bulk pricing tiers based on total campaign quantity.
* Customizable storefront title and description for Campaign Bulk Pricing offers.
* Quick Order, Editorial, and Compact storefront layouts.
* Campaign sections, images, rich content, and shortcode support.
* WooCommerce cart and session integration.
* Campaign attribution stored on WooCommerce order items.
* HPOS-compatible campaign reporting and refund-aware metrics.
* Password-protected live campaign reports with share links.
* Product-level performance in live reports.
* WooCommerce coupon compatibility and coexistence with dynamic pricing rules.
* Theme-aware presentation with isolated campaign commerce controls.

NOW Campaign Storefronts does not create a second product catalog, inventory system, cart, order system, discount engine, or financial ledger. WooCommerce remains authoritative for those areas.

== Installation ==

1. Install and activate WooCommerce.
2. Upload and activate NOW Campaign Storefronts for WooCommerce.
3. Open the Campaigns screen in the WordPress admin.
4. Create a campaign, add WooCommerce products or variations, and set campaign pricing.
5. Optional: enable Campaign Bulk Pricing and add quantity / percentage-off tiers.
6. Configure campaign content and sections.
7. Publish the campaign.
8. Optional: enable the password-protected external report and share its generated link.

== Frequently Asked Questions ==

= Does NOW Campaign Storefronts replace WooCommerce products or inventory? =

No. WooCommerce remains the product and inventory authority. Campaign products reference existing WooCommerce products or variations.

= Does it replace the WooCommerce cart or checkout? =

No. Campaign items use the WooCommerce cart, WooCommerce session, and normal checkout flow.

= How does Campaign Bulk Pricing work? =

Bulk Pricing counts eligible products and variations from the same campaign together. The highest reached quantity tier adjusts each eligible line from its own Campaign Price. Coupons and other WooCommerce discount integrations can still run afterward through the normal WooCommerce pricing flow.

= Can I customize the Campaign Bulk Pricing message? =

Yes. Each campaign can set its own storefront offer title and description while keeping the quantity-tier badges generated from the configured bulk-pricing rules.

= Does Campaign Bulk Pricing create another discount engine? =

No. Bulk tiers are treated as a quantity-dependent Campaign Price. WooCommerce remains authoritative for the cart, coupons, checkout, orders, refunds, and financial totals.

= Does it work with WooCommerce HPOS? =

Yes. NOW Campaign Storefronts declares compatibility with WooCommerce High-Performance Order Storage and stores campaign attribution on WooCommerce order items.

= Can a campaign contain variable products? =

Yes. Purchasable variations can be added as independent campaign items, each with its own campaign price and presentation.

= Can I use shortcodes in the campaign introduction? =

Yes. Campaign introduction content uses the normal WordPress content pipeline, so registered shortcodes can render there.

= How are live report passwords handled? =

External reports use WordPress Core password-protected-content behavior. NOW Campaign Storefronts stores the sharing password in an internal, non-public WordPress password record and WordPress manages the unlocked browser session through its standard wp-postpass cookie. Use a dedicated report sharing password and do not reuse an administrator or other sensitive account password.

= Does the plugin send store or customer data to an external service? =

No. NOW Campaign Storefronts does not make external service requests for campaign operation or reporting. Live reports are served by your own WordPress site and expose aggregate campaign metrics rather than customer contact details.

= Does it include customer details in the external report? =

No. External reports are designed around aggregate sales metrics and product performance and do not expose customer names, email addresses, phone numbers, addresses, or order numbers.

== Changelog ==

= 1.4.3 =
* Restored WooCommerce standard AJAX Add to Cart lifecycle compatibility for Campaign storefront purchases.
* Added WooCommerce fragments, cart hashes, and frontend added_to_cart events for third-party commerce and analytics integrations.
* Preserved the existing single-request Campaign cart experience for simple products, variations, and Add selected flows.
* Removed the redundant cart-fragment refresh request after successful Campaign cart updates.

= 1.4.2 =
* Renamed the public plugin identity to NOW Campaign Storefronts for WooCommerce.
* Updated the WordPress.org package slug and translation domain.
* Used WordPress asset enqueue APIs for the standalone Campaign Report package template.
* Removed bundled translation binaries so WordPress.org language packs supply translations.

= 1.4.1 =
* Bricks element conditions hardened: WC Campaign now evaluates its own Current Campaign condition as the final authority, resolves the Campaign from Bricks page context (including builder preview), and normalizes selected Campaign IDs robustly.
* Bricks dynamic data moved to a real provider pipeline: WC Campaign tags now resolve inside mixed text content (for example "開團：{wc_campaign_title}") in Text, Heading, Rich Text, and Button elements, not only as a whole-field tag.
* Campaign image dynamic data follows the Bricks image contract (attachment ID for the Image element, URL for text/link contexts).
* Campaign Products query loop id scoped to the campaign_products query type only, so other Bricks queries are never affected.

= 1.4.0 =
* Native Bricks Builder integration: Campaign pages can be designed and assigned with Bricks Single Templates.
* Bricks template ownership: when a Bricks content template is assigned to a Campaign, the native storefront template, automatic product append, and footer Mini Cart fallback stay out of the way.
* Campaign Products Query Loop: render Campaign products with Bricks query loops while keeping Campaign, section, product, and variation context.
* WC Campaign dynamic data tags for Campaign and Campaign Product values (title, product name, variation, image, reference price, Campaign price, savings, copy, stock note).
* Campaign-aware element conditions: show or hide shared Bricks elements by current Campaign (is / is not, multiple selection).
* The native storefront remains fully available when no Bricks template is assigned, and [woo_campaign_products] still renders the complete purchase UI inside Bricks templates.

= 1.3.0 =
* Added a Duplicate action to every Campaign row in the Campaign list.
* Duplicated Campaigns preserve campaign configuration, sections, products and variation references, campaign pricing, bulk pricing, and presentation while receiving new post, section, and product-relationship IDs.
* Duplicated Campaigns are created as drafts with a unique URL slug and never share the source external report link or password record.
* Order attribution and financial reporting stay isolated: a duplicated Campaign starts with zero sales, orders, and units, and the source Campaign is never modified.

= 1.2.0 =
* Fixed variable-product headings so selected variation attributes remain visible in Campaign cards.
* Fixed CTA fallback colors when no custom CTA or Campaign accent color is configured.
* Added Campaign Bulk Pricing with campaign-wide mix-and-match quantity tiers.
* Bulk tiers use each item's Campaign Price as the pricing baseline before WooCommerce coupon / dynamic-pricing processing.
* Added customizable storefront bulk-pricing title and description.
* Fixed bulk-pricing percentage inputs so whole-number discounts remain valid while decimal values are still supported.
* Aligned Bulk Pricing editor spacing with the rest of the Campaign editor.
* Mini Cart keeps Proceed to checkout as its only navigation action.
* Added campaign title visibility, unified product CTA sizing/colors/hover behavior, variation-aware product titles, product-copy color controls, and contain-fit product imagery.

= 1.1.1 =
* Initial WordPress.org submission.
* Added campaign storefront layouts and campaign pricing.
* Added WooCommerce order attribution and refund-aware reporting.
* Added password-protected live campaign reports using WordPress Core password protection.
* Added campaign rich content, image galleries, design controls, and shortcode support.
* Added theme-isolated quantity, add-to-cart, and campaign mini-cart controls.
* Fixed shortcode rendering in campaign introductions and hardened the WordPress.org release package.
