(function () {
  'use strict';

  function removeBrokenMedia(img) {
    if (!img || !img.closest) return;
    var container = img.closest('.woo-campaign-media-item, .woo-campaign-section-media, .woo-campaign-purchase-row-media, .woo-campaign-editorial-media, .woo-campaign-compact-card-media');
    if (!container) return;
    var item = container.closest('.woo-campaign-purchase-row, .woo-campaign-editorial-item, .woo-campaign-compact-card');
    if (item) item.classList.add('has-no-image');
    container.remove();
  }

  document.addEventListener('error', function (event) {
    if (event.target && event.target.matches && event.target.matches('.woo-campaign-page img')) {
      removeBrokenMedia(event.target);
    }
  }, true);

  function scan() {
    document.querySelectorAll('.woo-campaign-page img').forEach(function (img) {
      if (img.complete && img.naturalWidth === 0) removeBrokenMedia(img);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan);
  else scan();
})();
