# NOW Campaign Storefronts — WooCommerce 團購外掛

[English README](README.md) ·
[WordPress.org](https://wordpress.org/plugins/now-campaign-storefronts/) ·
[GitHub](https://github.com/bboyfan/now-campaign-storefronts)

> WooCommerce 團購外掛——幫你快速建立團購活動頁、設定開團專屬特惠價與混搭滿件折，並提供加密即時分潤報表給合作的 KOL 或團媽。不用複製商品、不用另建庫存，完全免費開源。

NOW Campaign Storefronts 是獨立的開源專案，並非 WooCommerce 或 Automattic 的官方產品。

## 誰適合用？

- **KOL / 網紅團購**：給每位合作者一個專屬團購網址 + 加密即時分潤報表，團媽隨時掌握開團業績，完全不洩漏買家個資。
- **團媽開團分銷**：搭配專屬連結與即時報表，輕鬆管理多場同時進行的團購活動。
- **檔期快閃特賣 / 限時優惠**：節慶、週年慶或限定活動，不動全店定價就能快速上線活動頁。
- **混搭滿件折促銷**：全場商品任選湊件數，「3 件 95 折、5 件 9 折」自動套用，有效提高客單價。
- **封館 / VIP 專屬賣場**：專屬活動網址 + 專屬優惠價，精準服務特定客群。

## 團購開團核心功能

- 直接在現有 WooCommerce 商品上設定開團專屬特惠價，原本售價完全不受影響，庫存扣同一批，不需要為了開團複製商品。
- 全場混搭滿件折（任選湊件折扣）：同活動商品任意混搭累計件數，達門檻自動套用階梯百分比折扣。
- 三種團購活動版型：快速下單（Quick Order）、圖文雜誌風（Editorial）、精簡卡片（Compact），手機與電腦都已優化。
- 底部懸浮購物車：一頁式團購體驗，選完數量直接滑到底部結帳，大幅降低跳出率。
- 一鍵複製開團：完整複製版型、選品、滿件折設定，換個網址就能立刻開賣新檔期。
- 活動頁支援區塊排版、圖片 Gallery、活動介紹、自訂色彩與 WordPress Shortcode。

## 網紅分潤報表 / KOL 開團追蹤

- 加密即時分潤報表：每場活動可設一組密碼與專屬報表連結，合作的 KOL 或團媽用手機打開就能即時看銷售件數、營收與退款扣除。**完全不顯示買家姓名、電話、地址與訂單編號**。
- 訂單自動歸屬：每筆從團購頁面下單的訂單，自動標記來源活動，方便追蹤每場開團成效。
- 退款自動扣除：報表營收數字即時扣除退款，看到的永遠是淨銷售額。

## 進階整合

- 原生支援 Bricks Builder：專屬 Single Template、Query Loop、Dynamic Data Tags 與活動條件判斷。
- 完美相容 WooCommerce：沿用 WooCommerce 原生庫存、購物車、結帳、優惠券與 HPOS，不建立第二套庫存或帳本。

## 畫面預覽

### 團購活動前台 (Campaign Storefront)
![團購活動前台——一頁式團購賣場、商品卡片、數量選擇器、滿件折優惠提示與懸浮購物車](docs/screenshots/campaign-storefront.png)

### 活動編輯器 (Campaign Editor)
![活動編輯器——區塊排版、WooCommerce 商品挑選、開團特惠價設定與版型選擇](docs/screenshots/campaign-editor.png)

### 滿件任選優惠 (Campaign Bulk Pricing)
![滿件任選優惠——混搭湊件階梯折扣設定：買 3 件 95 折、5 件 9 折、8 件 85 折](docs/screenshots/bulk-pricing.png)

### 即時分潤報表 (Live Campaign Report)
![即時分潤報表——加密分潤報表，含淨銷售額、訂單數、退款扣除與商品銷售明細](docs/screenshots/live-report.png)

### Campaign Bulk Pricing 計算邏輯

Bulk Pricing 是 **Campaign Price 的數量級距變化**，不是第二套折扣引擎。

例如同一個 Campaign 設定：

```text
3 件以上：5% off
5 件以上：10% off
8 件以上：15% off
```

若購物車內同一 Campaign 有：

```text
商品 A Campaign Price = 500
商品 B Campaign Price = 400
商品 C Campaign Price = 550
合計數量 = 4 → 未達 5 件門檻，套用 3 件級距（5% off）
```

則分別以每個商品自己的 Campaign Price 計算：

```text
商品 A = 475
商品 B = 380
商品 C = 522.5
```

不同 Campaign 不互相累計，一般 WooCommerce 商品也不會計入 Campaign 件數。Bulk Price 計算完成後，WooCommerce Coupon / 相容的 Dynamic Pricing 規則仍可依既有流程繼續處理。

## 系統需求

- WordPress 6.5 或更新版本。
- WooCommerce 8.0 或更新版本。
- PHP 8.1 或更新版本。

目前公開發行版本已測試至 WordPress 7.0 與 WooCommerce 10.9。

## 架構原則

NOW Campaign Storefronts 不建立第二套電商核心資料：

```text
WooCommerce Product / Variation = 商品 Authority
WooCommerce Inventory           = 庫存 Authority
WooCommerce Cart / Session      = 購物車 Authority
WooCommerce Coupon / Pricing    = 折扣 Authority
WooCommerce Order / Refund      = 財務 Authority

NOW Campaign Storefronts = 活動情境 + 活動價 + 訂單歸因 + 報表 + 呈現
```

也就是說，Campaign 只負責活動情境、活動價、訂單歸因、報表與呈現；實際商品、庫存、購物車、訂單、退款仍由 WooCommerce 負責。

## 安裝方式

1. 安裝並啟用 WooCommerce。
2. 從 [WordPress.org](https://wordpress.org/plugins/now-campaign-storefronts/) 安裝並啟用 NOW Campaign Storefronts for WooCommerce，或直接上傳外掛 ZIP。
3. 在 WordPress 後台開啟 Campaigns。
4. 建立 Campaign，加入 WooCommerce 商品或 Variations。
5. 設定 Campaign Price；如有需要，再啟用 Campaign Bulk Pricing 並新增件數／折扣級距。
6. 設定活動內容、圖片與商品區塊。
7. 發布 Campaign。
8. 如有需要，可啟用密碼保護的 External Report 並分享產生的網址。

## 相關連結

- **WordPress.org 官方外掛目錄**：[wordpress.org/plugins/now-campaign-storefronts](https://wordpress.org/plugins/now-campaign-storefronts/)
- **GitHub 開源儲存庫**：[github.com/bboyfan/now-campaign-storefronts](https://github.com/bboyfan/now-campaign-storefronts)

台灣開發者維護的開源團購外掛。如果對你的開團業務有幫助，歡迎到 GitHub 給一顆 ⭐ 支持，也歡迎提 Issue 或 Pull Request！

## 開發

此外掛使用 `Bboyfan\NowCampaignStorefronts\` PHP namespace，並保留 fallback autoloader。`composer.json` 會保留在公開 source 中，方便檢視與開發。

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
