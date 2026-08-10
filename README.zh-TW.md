# WC Campaign

[English README](README.md)

WC Campaign 是一個免費、開源、以 WooCommerce 為核心的 Campaign／團購活動外掛，用來建立獨立活動頁、Campaign 價格、混搭件數優惠、訂單歸因與即時業績報表，同時不取代 WooCommerce 原本的商品、庫存、購物車、訂單、退款與折扣系統。

> **專案狀態：** WC Campaign 1.3.0 是目前公開 Source 與 WordPress.org 更新送審版本；Plugin Directory submission 目前仍等待審核。

## 主要功能

- 支援 WooCommerce Simple Product 與 Variation 的 Campaign Price。
- 可在 Campaign 列表對任一 Campaign 執行「複製」，完整保留設定、商品區塊、商品與 Variation 引用、Campaign Price、Bulk Pricing 與 Presentation，並產生全新的內部識別。
- Campaign Bulk Pricing：同一 Campaign 商品與 Variations 可混搭累計件數，依設定門檻套用折扣。
- Bulk tier 以每個商品自己的 Campaign Price 為基準，不要求所有商品同價。
- Bulk Pricing 前台優惠標題與說明可依 Campaign 自訂。
- Quick Order、Editorial、Compact 三種活動商品版型。
- Variation 屬性直接整合進前台商品標題。
- Campaign Section、圖片 Gallery、活動介紹 Rich Text 與 Shortcode。
- Campaign 頁面可選擇隱藏可見 H1 標題，不影響 document title。
- 商品區塊可設定商品文案、CTA 背景與 CTA 文字顏色。
- 三種版型使用一致的「加入購物車」CTA 與稍微變亮的 hover 行為。
- 商品圖片統一使用置中 `contain`，避免裁切與變形。
- 沿用 WooCommerce Cart / Session，支援 Classic Checkout。
- Bottom Mini Cart 可調整數量／移除商品，導航只保留「前往結帳」。
- 將 Campaign attribution 寫入 WooCommerce order item，支援 HPOS。
- 退款感知的 Campaign 業績報表。
- 可設定密碼的對外即時報表分享連結，使用 WordPress Core `post_password` / `wp-postpass_*` 機制。
- 內建繁體中文 `zh_TW` 語系。

## 系統需求

- WordPress 6.5 或更新版本。
- WooCommerce 8.0 或更新版本。
- PHP 8.1 或更新版本。

目前版本已測試至 WordPress 7.0 與 WooCommerce 10.9。

## 架構原則

```text
WooCommerce Product / Variation = 商品 Authority
WooCommerce Inventory           = 庫存 Authority
WooCommerce Cart / Session      = 購物車 Authority
WooCommerce Coupon / Pricing    = 折扣 Authority
WooCommerce Order / Refund      = 財務 Authority

WC Campaign = Campaign Context + Campaign Price + Attribution + Reporting + Presentation
```

Campaign Bulk Pricing 是 **quantity-dependent Campaign Price**，不是另一套 Discount Engine。系統先依同一 Campaign 的合計件數找出 tier，再從各商品自己的 Campaign Price 算出 effective Campaign Price；Coupon / WDP 等後續折扣仍交由 WooCommerce 正常流程處理。

詳見 [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)。

## Campaign Bulk Pricing

Bulk Pricing 以同一 Campaign 內 eligible 商品與 Variation 的合計件數判斷門檻，因此適合團購任選混搭。例如：

```text
2 件以上 → 5% off
4 件以上 → 10% off
8 件以上 → 15% off
```

假設三個商品的 Campaign Price 分別是 500、400、550，達到 4 件 10% off 時，effective Campaign Price 分別為 450、360、495。之後 Coupon / WDP、Checkout、Order totals 與 Refund 都仍由 WooCommerce 處理。

## Campaign 複製

Campaign 列表的每一列都有「複製」動作。複製會建立一個新的 **draft** Campaign，內容、商品區塊、商品與 Variation 引用、Campaign Price、Bulk Pricing 與 Presentation 與來源完全一致。新 Campaign 會取得全新的 post、section 與商品關聯 ID，以及全新的對外報表 share key 與密碼記錄，因此不會共用來源的報表連結或訂單歸因。

## 安裝方式

1. 安裝並啟用 WooCommerce。
2. 安裝並啟用 WC Campaign。
3. 在 WordPress 後台建立 Campaign，加入商品或 Variations。
4. 設定 Campaign Price、內容、圖片與商品區塊。
5. 如有需要，啟用 Campaign Bulk Pricing。
6. 發布 Campaign。

## 活動介紹與 Shortcode

「活動介紹」會經過 WordPress 標準 `the_content` 流程，因此可以使用已註冊的 Shortcode。

## 即時報表與隱私

External Report 保留 `/campaign-report/{share-key}/` 分享網址，密碼驗證交給 WordPress Core Password Protected Content。報表顯示彙總業績與商品表現，不顯示顧客個資或訂單號碼。

詳見 [docs/PRIVACY.md](docs/PRIVACY.md)。

## 多語系

公開版本使用英文作為 canonical source strings，並提供繁體中文 `zh_TW` 翻譯於 `languages/`。

## Changelog

版本更新紀錄請見 [CHANGELOG.md](CHANGELOG.md)。

## 開源授權

WC Campaign 以 **GPL-2.0-or-later** 授權。詳見 [OPEN_SOURCE.md](OPEN_SOURCE.md) 與 [LICENSE](LICENSE)。

WC Campaign 是獨立的開源專案，並非 WooCommerce 或 Automattic 官方產品。

## 開發與貢獻

詳見 [CONTRIBUTING.md](CONTRIBUTING.md)、[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) 與 [SECURITY.md](SECURITY.md)。
