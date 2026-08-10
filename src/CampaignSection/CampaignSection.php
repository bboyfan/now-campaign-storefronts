<?php

namespace WooCampaign\CampaignSection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignSection {
	public const LAYOUT_QUICK_ORDER = 'quick_order';
	public const LAYOUT_EDITORIAL = 'editorial';
	public const LAYOUT_COMPACT_GRID = 'compact_grid';

	public function __construct(
		public readonly int $id,
		public readonly int $campaignId,
		public readonly string $title,
		public readonly string $description,
		public readonly int $imageId,
		public readonly string $layout,
		public readonly string $status,
		public readonly int $displayOrder,
		public readonly string $titleColor = '',
		public readonly string $ctaBgColor = '',
		public readonly string $ctaTextColor = '',
		public readonly string $copyColor = '',
	) {}

	public static function layouts(): array {
		return [
			self::LAYOUT_QUICK_ORDER,
			self::LAYOUT_EDITORIAL,
			self::LAYOUT_COMPACT_GRID,
		];
	}

	public function isActive(): bool {
		return 'active' === $this->status;
	}
}