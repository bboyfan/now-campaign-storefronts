(function () {
  'use strict';

  function removeBrokenMedia(img) {
    if (!img || !img.closest) return;
    var container = img.closest('.nowcastf-media-item, .woo-campaign-media-item, .nowcastf-section-media, .woo-campaign-section-media, .nowcastf-purchase-row-media, .woo-campaign-purchase-row-media, .nowcastf-editorial-media, .woo-campaign-editorial-media, .nowcastf-compact-card-media, .woo-campaign-compact-card-media');
    if (!container) return;
    var item = container.closest('.nowcastf-purchase-row, .woo-campaign-purchase-row, .nowcastf-editorial-item, .woo-campaign-editorial-item, .nowcastf-compact-card, .woo-campaign-compact-card');
    if (item) item.classList.add('has-no-image');
    container.remove();
  }

  document.addEventListener('error', function (event) {
    if (event.target && event.target.matches && (event.target.matches('.nowcastf-page img') || event.target.matches('.woo-campaign-page img'))) {
      removeBrokenMedia(event.target);
    }
  }, true);

  function scan() {
    document.querySelectorAll('.nowcastf-page img, .woo-campaign-page img').forEach(function (img) {
      if (img.complete && img.naturalWidth === 0) removeBrokenMedia(img);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan);
  else scan();
})();
