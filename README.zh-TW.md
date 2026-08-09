# WC Campaign

[English README](README.md)

WC Campaign 是一個免費、開源、以 WooCommerce 為核心的 Campaign／團購活動外掛，用來建立獨立活動頁、Campaign 價格、訂單歸因與即時業績報表，同時不取代 WooCommerce 原本的商品、庫存、購物車、訂單、退款與折扣系統。

> **專案狀態：** WC Campaign 1.1.1 已提交 WordPress.org Plugin Directory，目前等待人工審核。

## 主要功能

- 支援 WooCommerce Simple Product 與 Variation 的 Campaign 價格。
- Quick Order、Editorial、Compact 三種活動商品版型。
- Campaign Section、圖片 Gallery、活動介紹 Rich Text 與 Shortcode。
- 沿用 WooCommerce Cart / Session，支援 Classic Checkout。
- 將 Campaign attribution 寫入 WooCommerce order item，支援 HPOS。
- 退款感知的 Campaign 業績報表。
- 可設定密碼的對外即時報表分享連結。
- 對外報表使用 WordPress Core 原生 `post_password` / `wp-postpass_*` 密碼機制，不另建一套登入 Session。
- 對外報表可查看商品層級表現，但不顯示顧客姓名、Email、電話、地址或訂單號碼。
- 前台承接 Theme 的一般視覺風格，同時隔離 Campaign 的數量控制、Add to Cart 與 Bottom Mini Cart，降低 Theme 全域樣式造成的跑版。
- 內建繁體中文 `zh_TW` 語系。

## 系統需求

- WordPress 6.5 或更新版本。
- WooCommerce 8.0 或更新版本。
- PHP 8.1 或更新版本。

目前版本已測試至 WordPress 7.0 與 WooCommerce 10.9。

## 架構原則

WC Campaign 不建立第二套電商核心資料：

```text
WooCommerce Product / Variation = 商品 Authority
WooCommerce Inventory           = 庫存 Authority
WooCommerce Cart / Session      = 購物車 Authority
WooCommerce Coupon / Pricing    = 折扣 Authority
WooCommerce Order / Refund      = 財務 Authority

WC Campaign = Campaign Context + Campaign Price + Attribution + Reporting + Presentation
```

也就是說，Campaign 只負責活動情境、活動價、訂單歸因、報表與呈現；實際商品、庫存、購物車、訂單、退款仍由 WooCommerce 負責。

詳見 [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)。

## 安裝方式

1. 安裝並啟用 WooCommerce。
2. 安裝並啟用 WC Campaign。
3. 在 WordPress 後台開啟 Campaigns。
4. 建立 Campaign，加入 WooCommerce 商品或 Variations。
5. 設定 Campaign Price、活動內容、圖片與商品區塊。
6. 發布 Campaign。
7. 如有需要，可啟用密碼保護的 External Report 並分享產生的網址。

## 活動介紹與 Shortcode

「活動介紹」會經過 WordPress 標準 `the_content` 流程，因此可以使用已註冊的 Shortcode，例如由 Theme 或其他外掛提供的 Template Shortcode。

## 即時報表與隱私

External Report 保留 `/campaign-report/{share-key}/` 分享網址，但密碼驗證交給 WordPress Core 原生 Password Protected Content。登入後使用的是 WordPress 標準 `wp-postpass_*` cookie，而不是 WC Campaign 自行維護另一套登入 cookie／session。

報表顯示彙總業績與商品表現，公開頁面不會顯示顧客個資或訂單明細。

詳見 [docs/PRIVACY.md](docs/PRIVACY.md)。

## 多語系

公開版本使用英文作為 canonical source strings，並提供繁體中文 `zh_TW` 翻譯於 `languages/`。

## Changelog

版本更新紀錄與重要實作變更請見 [CHANGELOG.md](CHANGELOG.md)。

## 開源授權

WC Campaign 是自由軟體，以 **GPL-2.0-or-later** 授權。你可以依照 GPL 條款使用、研究、修改與再散布程式碼。

詳見 [OPEN_SOURCE.md](OPEN_SOURCE.md) 與 [LICENSE](LICENSE)。

WC Campaign 是獨立的開源專案，並非 WooCommerce 或 Automattic 官方產品。

## 開發與貢獻

此外掛使用 `WooCampaign\\` PHP namespace，並保留 fallback autoloader。`composer.json` 保留在公開 source 中，方便檢視與開發。

提交修改前應至少確認 PHP/JavaScript syntax，以及與修改範圍相關的 Campaign Price、Coupon / Dynamic Pricing、Cart / Session、HPOS attribution、Reporting / Refund、Storefront 與 Live Report authentication。

詳見 [CONTRIBUTING.md](CONTRIBUTING.md) 與 [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)。

## 安全性

請不要在公開 Issue 中揭露安全漏洞，詳見 [SECURITY.md](SECURITY.md)。

## License

GPL-2.0-or-later，詳見 [LICENSE](LICENSE)。
