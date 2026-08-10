(function ($) {
  'use strict';

  var state = window.WooCampaignBulkPricing || {};
  if (!state.config) return;

  var i18n = state.i18n || {};
  var config = {
    enabled: !!state.config.enabled,
    notice_title: String(state.config.notice_title || i18n.defaultNoticeTitle || ''),
    notice_description: String(state.config.notice_description || i18n.defaultNoticeDescription || ''),
    tiers: Array.isArray(state.config.tiers) ? state.config.tiers.map(function (tier) {
      return {
        min_qty: Math.max(2, parseInt(tier.min_qty || 2, 10)),
        discount_percent: Number(tier.discount_percent || 0)
      };
    }) : []
  };

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function ensureHiddenInput() {
    var form = document.getElementById('woo-campaign-editor-form');
    if (!form) return null;
    var input = document.getElementById('woo-campaign-bulk-pricing-json');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.id = 'woo-campaign-bulk-pricing-json';
      input.name = 'campaign_bulk_pricing_json';
      form.appendChild(input);
    }
    return input;
  }

  function tierMarkup(tier, index) {
    return '<div class="woo-campaign-bulk-tier" data-bulk-tier data-tier-index="' + index + '">' +
      '<label><span>' + escapeHtml(i18n.quantity || 'Minimum quantity') + '</span><div class="woo-campaign-bulk-input-with-unit"><input type="number" min="2" step="1" value="' + Number(tier.min_qty || 2) + '" data-bulk-min-qty><small>' + escapeHtml(i18n.quantityUnit || 'or more') + '</small></div></label>' +
      '<label><span>' + escapeHtml(i18n.discount || 'Discount (%)') + '</span><input type="number" min="0.01" max="99.99" step="0.01" value="' + escapeHtml(tier.discount_percent || '') + '" data-bulk-discount></label>' +
      '<button type="button" class="button-link-delete woo-campaign-bulk-tier-remove" data-bulk-remove aria-label="' + escapeHtml(i18n.removeTier || 'Remove tier') + '"><span class="dashicons dashicons-trash"></span></button>' +
    '</div>';
  }

  function tiersMarkup() {
    if (!config.tiers.length) {
      return '<div class="woo-campaign-bulk-empty" data-bulk-empty>' + escapeHtml(i18n.empty || 'No pricing tiers yet.') + '</div>';
    }
    return config.tiers.map(tierMarkup).join('');
  }

  function renderTiers() {
    var list = document.querySelector('[data-bulk-tier-list]');
    if (!list) return;
    list.innerHTML = tiersMarkup();
  }

  function syncDisabledState() {
    var card = document.querySelector('[data-campaign-bulk-pricing-card]');
    if (!card) return;
    card.classList.toggle('is-disabled', !config.enabled);
    var toggle = card.querySelector('[data-bulk-enabled]');
    if (toggle) toggle.checked = config.enabled;
  }

  function injectCard() {
    if (document.querySelector('[data-campaign-bulk-pricing-card]')) return;
    var sectionsCard = document.querySelector('.woo-campaign-sections-card');
    var main = document.querySelector('.woo-campaign-editor-main');
    if (!sectionsCard || !main) return;

    var card = document.createElement('section');
    card.className = 'woo-campaign-editor-card woo-campaign-bulk-pricing-card';
    card.setAttribute('data-campaign-bulk-pricing-card', '');
    card.innerHTML =
      '<div class="woo-campaign-editor-card-heading"><div><span class="woo-campaign-editor-eyebrow">' + escapeHtml(i18n.eyebrow || 'Campaign pricing') + '</span><h2>' + escapeHtml(i18n.title || 'Campaign Bulk Pricing') + '</h2></div><p>' + escapeHtml(i18n.description || '') + '</p></div>' +
      '<div class="woo-campaign-bulk-pricing-content">' +
        '<label class="woo-campaign-bulk-toggle"><input type="checkbox" data-bulk-enabled' + (config.enabled ? ' checked' : '') + '><span><strong>' + escapeHtml(i18n.enable || 'Enable Campaign Bulk Pricing') + '</strong><small>' + escapeHtml(i18n.scope || '') + '</small></span></label>' +
        '<div class="woo-campaign-bulk-body" data-bulk-body>' +
          '<div class="woo-campaign-bulk-help">' + escapeHtml(i18n.baseHelp || '') + '</div>' +
          '<div class="woo-campaign-bulk-copy-fields">' +
            '<label><span>' + escapeHtml(i18n.noticeTitleLabel || 'Storefront offer title') + '</span><input type="text" value="' + escapeHtml(config.notice_title) + '" data-bulk-notice-title></label>' +
            '<label><span>' + escapeHtml(i18n.noticeDescriptionLabel || 'Storefront offer description') + '</span><textarea rows="2" data-bulk-notice-description>' + escapeHtml(config.notice_description) + '</textarea></label>' +
          '</div>' +
          '<div class="woo-campaign-bulk-tier-list" data-bulk-tier-list>' + tiersMarkup() + '</div>' +
          '<button type="button" class="button" data-bulk-add-tier><span class="dashicons dashicons-plus-alt2"></span>' + escapeHtml(i18n.addTier || 'Add tier') + '</button>' +
        '</div>' +
      '</div>';

    main.insertBefore(card, sectionsCard);
    syncDisabledState();
  }

  function serialize() {
    var input = ensureHiddenInput();
    if (!input) return;
    config.tiers.sort(function (a, b) { return Number(a.min_qty) - Number(b.min_qty); });
    input.value = JSON.stringify(config);
  }

  ensureHiddenInput();
  injectCard();

  $(document).on('change', '[data-bulk-enabled]', function () {
    config.enabled = this.checked;
    syncDisabledState();
  });

  $(document).on('input', '[data-bulk-notice-title]', function () {
    config.notice_title = String(this.value || '');
  });

  $(document).on('input', '[data-bulk-notice-description]', function () {
    config.notice_description = String(this.value || '');
  });

  $(document).on('click', '[data-bulk-add-tier]', function () {
    var last = config.tiers.length ? config.tiers[config.tiers.length - 1] : null;
    config.tiers.push({
      min_qty: last ? Math.max(2, Number(last.min_qty) + 1) : 2,
      discount_percent: last ? Number(last.discount_percent || 0) : 5
    });
    renderTiers();
  });

  $(document).on('click', '[data-bulk-remove]', function () {
    var index = Number($(this).closest('[data-bulk-tier]').attr('data-tier-index'));
    if (index >= 0 && index < config.tiers.length) config.tiers.splice(index, 1);
    renderTiers();
  });

  $(document).on('input', '[data-bulk-min-qty]', function () {
    var index = Number($(this).closest('[data-bulk-tier]').attr('data-tier-index'));
    if (!config.tiers[index]) return;
    config.tiers[index].min_qty = Math.max(2, parseInt(this.value || '2', 10));
  });

  $(document).on('input', '[data-bulk-discount]', function () {
    var index = Number($(this).closest('[data-bulk-tier]').attr('data-tier-index'));
    if (!config.tiers[index]) return;
    config.tiers[index].discount_percent = Math.max(0, Math.min(99.99, Number(this.value || 0)));
  });

  $('#woo-campaign-editor-form').on('submit', serialize);
})(jQuery);
