# NOW Campaign Storefronts for WooCommerce

[English README](README.md)

NOW Campaign Storefronts for WooCommerce 是一個以 WooCommerce 為核心的 Campaign／團購活動外掛，用來建立獨立活動頁、Campaign 價格、訂單歸因與即時業績報表，同時不取代 WooCommerce 原本的商品、庫存、購物車、訂單、退款與折扣系統。

NOW Campaign Storefronts 是獨立的開源專案，並非 WooCommerce 或 Automattic 的官方產品。

## 主要功能

- 支援 WooCommerce Simple Product 與 Variation 的 Campaign 價格。
- 支援 Campaign Bulk Pricing：同一 Campaign 的商品與 Variations 可混搭累計件數，依級距套用折扣。
- Quick Order、Editorial、Compact 三種活動商品版型。
- Campaign Section、圖片 Gallery、活動介紹 Rich Text 與 Shortcode。
- 沿用 WooCommerce Cart / Session，支援 Classic Checkout。
- 將 Campaign attribution 寫入 WooCommerce order item，支援 HPOS。
- 退款感知的 Campaign 業績報表。
- 可設定密碼的對外即時報表分享連結。
- 對外報表可查看商品層級表現，但不顯示顧客姓名、Email、電話、地址或訂單號碼。
- 前台會承接 Theme 的一般視覺風格，同時隔離 Campaign 的數量控制、Add to Cart 與 Bottom Mini Cart，降低 Theme 全域樣式造成的跑版。

### Campaign Bulk Pricing

Bulk Pricing 是 **Campaign Price 的數量級距變化**，不是第二套折扣引擎。

例如同一個 Campaign 設定：

```text
2 件以上：5% off
4 件以上：10% off
8 件以上：15% off
```

若購物車內同一 Campaign 有：

```text
商品 A Campaign Price = 500
商品 B Campaign Price = 400
商品 C Campaign Price = 550
合計數量 = 4
```

則 4 件級距會分別以每個商品自己的 Campaign Price 計算：

```text
商品 A = 450
商品 B = 360
商品 C = 495
```

不同 Campaign 不互相累計，一般 WooCommerce 商品也不會計入 Campaign 件數。Bulk Price 計算完成後，WooCommerce Coupon / 相容的 Dynamic Pricing 規則仍可依既有流程繼續處理。

## 系統需求

- WordPress 6.5 或更新版本。
- WooCommerce 8.0 或更新版本。
- PHP 8.1 或更新版本。

目前公開發行候選版本已測試至 WordPress 7.0 與 WooCommerce 10.9。

## 架構原則

NOW Campaign Storefronts 不建立第二套電商核心資料：

```text
WooCommerce Product / Variation = 商品 Authority
WooCommerce Inventory           = 庫存 Authority
WooCommerce Cart / Session      = 購物車 Authority
WooCommerce Coupon / Pricing    = 折扣 Authority
WooCommerce Order / Refund      = 財務 Authority

NOW Campaign Storefronts = Campaign Context + Campaign Price + Attribution + Reporting + Presentation
```

也就是說，Campaign 只負責活動情境、活動價、訂單歸因、報表與呈現；實際商品、庫存、購物車、訂單、退款仍由 WooCommerce 負責。

## 安裝方式

1. 安裝並啟用 WooCommerce。
2. 安裝並啟用 NOW Campaign Storefronts for WooCommerce。
3. 在 WordPress 後台開啟 Campaigns。
4. 建立 Campaign，加入 WooCommerce 商品或 Variations。
5. 設定 Campaign Price；如有需要，再啟用 Campaign Bulk Pricing 並新增件數／折扣級距。
6. 設定活動內容、圖片與商品區塊。
7. 發布 Campaign。
8. 如有需要，可啟用密碼保護的 External Report 並分享產生的網址。

## 下載

請從 [GitHub 最新版本](https://github.com/bboyfan/now-campaign-storefronts/releases/latest) 下載 [NOW Campaign Storefronts 1.4.4](https://github.com/bboyfan/now-campaign-storefronts/releases/download/v1.4.4/now-campaign-storefronts-1.4.4.zip)。

SHA256：[`now-campaign-storefronts-1.4.4.sha256`](https://github.com/bboyfan/now-campaign-storefronts/raw/main/releases/now-campaign-storefronts-1.4.4.sha256)

## 活動介紹與 Shortcode

「活動介紹」會經過 WordPress 標準 `the_content` 流程，因此可以使用已註冊的 Shortcode，例如由 Theme 或其他外掛提供的 Template Shortcode。

## 即時報表

External Report 可以設定分享密碼與獨立分享網址，並顯示：

- Net Sales
- Paid Orders
- Units
- Average Order
- Pending Orders
- Campaign Subtotal
- Discount
- Refund
- Refunded Units
- 商品表現

Report 的公開頁面只提供彙總業績與商品表現，不會顯示顧客個資或訂單明細。

## 多語系

WordPress.org 發行版使用英文作為 canonical source strings，繁體中文等語言由 WordPress.org language packs 提供。

Repo 中的 `scripts/build-wordpress-org.sh` 會建立 WordPress.org 專用的 `now-campaign-storefronts` package，不會把 `.po` 或 `.mo` 檔案放進 ZIP。

WordPress.org 上架後，可透過 translate.wordpress.org 維護其他語言與繁中翻譯。

## 開發

此外掛使用 `NowCampaignStorefronts\` PHP namespace，並保留 fallback autoloader。`composer.json` 會保留在公開 source 中，方便檢視與開發。

提交修改前應至少確認：

- PHP syntax
- JavaScript syntax
- Campaign Price / Campaign Bulk Pricing
- WooCommerce Coupon / Dynamic Pricing 相容性
- Cart / Session
- HPOS attribution
- Reporting / Refund
- Campaign storefront
- Live Report authentication

## 安全性

請不要在公開 Issue 中揭露安全漏洞，詳見 [SECURITY.md](SECURITY.md)。

## Contributing

詳見 [CONTRIBUTING.md](CONTRIBUTING.md)。

## License

GPL-2.0-or-later，詳見 [LICENSE](LICENSE)。
