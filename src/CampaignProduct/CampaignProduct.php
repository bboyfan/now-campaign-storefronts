<?php

namespace Bboyfan\NowCampaignStorefronts\CampaignProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignProduct {
	public function __construct(
		public readonly int $id,
		public readonly int $campaignId,
		public readonly int $sectionId,
		public readonly int $productId,
		public readonly int $variationId,
		public readonly string $campaignPrice,
		public readonly string $campaignCopy,
		public readonly string $status,
		public readonly int $displayOrder,
	) {}

	public function saleableId(): int {
		return $this->variationId > 0 ? $this->variationId : $this->productId;
	}

	public function isActive(): bool {
		return 'active' === $this->status;
	}
}
