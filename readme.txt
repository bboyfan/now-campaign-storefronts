=== NOW Campaign Storefronts for WooCommerce ===
Contributors: bboyfan
Tags: woocommerce, campaigns, group buying, reporting, storefront
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 1.4.5
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build campaign storefronts for WooCommerce with campaign pricing, layouts, attribution, live reports, and protected sharing.

== Description ==

**NOW Campaign Storefronts for WooCommerce** 是專為 WooCommerce 打造的「團購分銷、KOL 網紅合作、限時特賣與檔期活動頁」外掛。讓您無需重複建立商品或複製庫存，即可快速為特定活動建立專屬的活動賣場與獨立分潤報表。

本外掛為獨立開源專案，非 WooCommerce 或 Automattic 官方產品。

### 🌟 核心特色

* **活動專屬特惠價**：直接針對現有 WooCommerce 單一商品或規格商品設定活動特惠價，不影響原商城正常售價。
* **混搭全場滿件折（Campaign Bulk Pricing）**：支援同活動內跨商品／跨規格「混搭湊件數」享階梯百分比折扣（例如：任選 3 件 9 折、5 件 8 折）。
* **一鍵複製活動（Duplicate Campaign）**：支援直接複製既有活動設定、版面、商品清單與階梯定價，快速複製開團檔期。
* **三大原生賣場版型**：提供快速下單（Quick Order）、圖文誌（Editorial）及精簡卡片（Compact）三種活動版型，手機與電腦端皆經過極致優化。
* **完整支援區塊 Builder 與 Shortcode**：活動內頁支援區塊排版、圖片、自訂色彩、自訂樣式及 WordPress Shortcode。
* **原生整合 Bricks Builder**：提供專屬 Single Template 範本、查詢迴圈（Query Loop）、條件判斷（Conditions）與動態資料標籤（Dynamic Data Tags）。
* **密碼保護的獨立即時分潤報表**：提供專屬加密分享連結，合作 KOL／團媽輸入密碼即可即時查看銷售件數、訂單狀態與業績統計；**完全不洩漏買家個資（無姓名、電話、地址與訂單編號）**，安全合規。
* **精準訂單歸屬與退款扣除**：活動來源自動標記於 WooCommerce 訂單項目，報表具備即時退款扣除計算，財務數據一目了然。
* **完美相容 WooCommerce 原生生態**：完全沿用 WooCommerce 原生商品庫存、購物車、結帳流程、優惠券與 HPOS（高效能訂單儲存），不建立第二套庫存或帳本。

### 🎯 適合的使用情境

* **KOL / 網紅團購分銷**：提供專屬團購網址與密碼保護的分潤報表，讓團媽隨時掌握開團成效。
* **檔期快閃特賣**：節慶、週年慶或限定優惠活動，無需更動全店商品定價即可快速上線。
* **封館與 VIP 專屬賣場**：結合專屬活動網址，提供特定客群專屬優惠。
* **多件組合促銷**：利用全場混搭階梯折扣，有效提高客單價（AOV）。

### 🌟 開源專案與社群支持

* **GitHub 開源儲存庫**：[https://github.com/bboyfan/now-campaign-storefronts](https://github.com/bboyfan/now-campaign-storefronts)
* 如果這個外掛對您的商店或團購業務有幫助，歡迎到 GitHub 給我們一顆 **Star ⭐** 支持！也歡迎提交 Issue 或 Pull Request 一起讓外掛更完善。

---

### English Description

NOW Campaign Storefronts for WooCommerce adds campaign-specific storefronts and reporting while keeping WooCommerce as the source of truth for products, inventory, carts, orders, refunds, and discounts.

NOW Campaign Storefronts is an independent open-source project and is not an official WooCommerce or Automattic product.

It is designed for stores that run group buys, influencer campaigns, limited-time sales, private campaign pages, or other campaign-specific storefronts without duplicating WooCommerce product or order data.

**Key Features:**

* **Campaign-Specific Pricing**: Set special campaign pricing for WooCommerce simple products and variations without altering standard catalog prices.
* **Campaign-Wide Mix-and-Match Bulk Pricing**: Tiered percentage discounts based on total campaign items purchased across multiple products and variations.
* **One-Click Campaign Duplication**: Duplicate any campaign with all sections, products, bulk pricing, and visual styling preserved under fresh internal IDs.
* **Three Dedicated Storefront Layouts**: Quick Order, Editorial, and Compact layouts, fully responsive for desktop and mobile devices.
* **Rich Content & Shortcode Support**: Campaign sections, images, rich text descriptions, color customization, and shortcodes.
* **Native Bricks Builder Integration**: Custom Single Templates, Query Loops, Dynamic Data Tags, and campaign display conditions.
* **Password-Protected Live Reports**: Shareable live reporting links for influencers/collaborators with aggregate sales metrics and **zero customer personal data exposure**.
* **Accurate Attribution & Refund Deductions**: Tracks campaign sales directly on WooCommerce order items with refund-aware net revenue metrics.
* **100% WooCommerce Native**: Fully compatible with WooCommerce HPOS, carts, coupons, checkouts, and inventory management.

**Open Source & Community:**

* **GitHub Repository**: [https://github.com/bboyfan/now-campaign-storefronts](https://github.com/bboyfan/now-campaign-storefronts)
* If you find this plugin helpful, please consider giving us a **Star ⭐ on GitHub**! Feedback, feature requests, and contributions are warmly welcome.

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

Bulk Pricing counts the total quantity across all products and variations purchased inside the same Campaign. When a threshold is met, each product's Campaign Price receives the percentage discount before WooCommerce handles standard coupons or downstream calculations.

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

== Screenshots ==

1. Campaign Storefront with multi-product merchandising, quantity pickers, tier savings notices, and floating mini cart.
2. Campaign Editor for section-based layouts, WooCommerce product selection, campaign pricing, and content controls.
3. Campaign Bulk Pricing for campaign-wide mix-and-match quantity tier discounts.
4. Live Campaign Report for revenue, order metrics, refunds, pending checkouts, and product breakdown.

== Changelog ==

= 1.4.5 =
* Addressed second-round WordPress.org manual review feedback with vendor-scoped PHP namespace and JavaScript globals.
* Replaced raw $_POST exposure in cart compatibility actions with minimal sanitized context parameters.
* Hardened request boundary sanitization for all scalar and JSON data structures across editor, bulk pricing, and AJAX endpoints.

= 1.4.4 =
* Addressed WordPress.org manual review requirements with unified canonical prefixing and hardened sanitization.
* Updated storefront product media display to cover framing across all section layouts.
* Refined Campaign Report typography, card heights, and responsive metric hierarchy.

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
* The native storefront remains fully available when no Bricks template is assigned, and [nowcastf_products] still renders the complete purchase UI inside Bricks templates.

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
