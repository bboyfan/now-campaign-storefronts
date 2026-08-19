(function ($) {
  'use strict';

  var settings = window.NowCastfAdminSettings || {};
  var activeModal = null;

  function initProductSearch() {
    $(document.body).trigger('wc-enhanced-select-init');
  }

  function updatePanelState($panel) {
    var count = $panel.find('.woo-campaign-product-row').length;
    $panel.find('[data-woo-campaign-product-count]').text(count);
    $panel.find('[data-woo-campaign-products-empty]').prop('hidden', count > 0);
    $panel.find('.woo-campaign-products-table').toggleClass('is-empty', count === 0);
    $panel.find('.woo-campaign-product-row, .nowcastf-product-row').each(function (index) {
      $(this).find('input[name="nowcastf_display_order[]"]').val(index);
    });
    $panel.attr('data-next-order', String(count));
  }

  function updateSavings($row) {
    var base = parseFloat($row.find('[data-woo-base-price]').attr('data-woo-base-price') || '0');
    var campaign = parseFloat($row.find('[data-woo-campaign-price-input]').val() || '0');
    var $preview = $row.find('[data-woo-campaign-saving-preview]');

    if (!base || !campaign || campaign >= base) {
      $preview.text('').removeClass('is-saving is-premium');
      return;
    }

    var percentage = Math.round((1 - campaign / base) * 100);
    $preview.text((settings.i18n && settings.i18n.saves ? settings.i18n.saves : 'Save') + ' ' + percentage + '%').addClass('is-saving').removeClass('is-premium');
  }

  function updateAllSavings(scope) {
    $(scope || document).find('.woo-campaign-product-row').each(function () {
      updateSavings($(this));
    });
  }

  function rowExists($panel, saleableId, $exclude) {
    var found = false;
    $panel.find('.woo-campaign-product-row').each(function () {
      var $row = $(this);
      if ($exclude && $row.is($exclude)) return;
      var current = parseInt($row.find('select.wc-product-search').val() || $row.attr('data-saleable-id') || '0', 10);
      if (current === parseInt(saleableId, 10)) found = true;
    });
    return found;
  }

  function closeModal() {
    if (!activeModal) return;
    activeModal.remove();
    activeModal = null;
    $('body').removeClass('nowcastf-modal-open');
  }

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function escapeAttr(str) {
    return escapeHtml(str);
  }

  function selectedCount($modal) {
    return $modal.find('.woo-campaign-var-check:checked').length;
  }

  function refreshModalSelection($modal) {
    var count = selectedCount($modal);
    var $button = $modal.find('.woo-campaign-modal-add');
    var label = settings.i18n && settings.i18n.addVariations ? settings.i18n.addVariations : 'Add selected variations';
    $button.prop('disabled', count === 0).text(label + (count ? ' (' + count + ')' : ''));
    $modal.find('[data-woo-campaign-selected-count]').text(count);
  }

  function openVariationPicker(data, $targetRow, $panel) {
    closeModal();

    var html = '<div class="nowcastf-variation-modal-overlay" role="presentation">';
    html += '<div class="nowcastf-variation-modal" role="dialog" aria-modal="true" aria-labelledby="nowcastf-variation-modal-title">';
    html += '<div class="nowcastf-modal-header">';
    html += '<div><span class="nowcastf-modal-eyebrow">' + escapeHtml(settings.i18n && settings.i18n.variableProduct ? settings.i18n.variableProduct : 'Variable product') + '</span>';
    html += '<h2 id="nowcastf-variation-modal-title">' + escapeHtml(data.parent_name) + '</h2>';
    html += '<p>' + escapeHtml(settings.i18n && settings.i18n.chooseVariationsHelp ? settings.i18n.chooseVariationsHelp : 'Choose the variations you want to sell in this campaign and set their Campaign Prices.') + '</p></div>';
    html += '<button type="button" class="nowcastf-modal-close" aria-label="' + escapeAttr(settings.i18n && settings.i18n.cancel ? settings.i18n.cancel : 'Close') + '"><span class="dashicons dashicons-no-alt"></span></button>';
    html += '</div>';

    if (!data.variations || !data.variations.length) {
      html += '<div class="nowcastf-modal-empty"><span class="dashicons dashicons-info-outline"></span><p>' + escapeHtml(settings.i18n && settings.i18n.noVariationsFound ? settings.i18n.noVariationsFound : 'No variations found.') + '</p></div>';
    } else {
      html += '<div class="nowcastf-modal-selection-bar">';
      html += '<label><input type="checkbox" class="nowcastf-modal-select-all"> ' + escapeHtml(settings.i18n && settings.i18n.selectAll ? settings.i18n.selectAll : 'Select all') + '</label>';
      html += '<span><strong data-woo-campaign-selected-count>0</strong> ' + escapeHtml(settings.i18n && settings.i18n.selected ? settings.i18n.selected : 'selected') + '</span>';
      html += '</div>';
      html += '<div class="nowcastf-modal-table-wrap"><table class="widefat woo-campaign-modal-table">';
      html += '<thead><tr><th></th><th>' + escapeHtml(settings.i18n && settings.i18n.variation ? settings.i18n.variation : 'Variation') + '</th><th>' + escapeHtml(settings.i18n && settings.i18n.wooPrice ? settings.i18n.wooPrice : 'Woo price') + '</th><th>' + escapeHtml(settings.i18n && settings.i18n.stock ? settings.i18n.stock : 'Stock') + '</th><th>' + escapeHtml(settings.i18n && settings.i18n.campaignPrice ? settings.i18n.campaignPrice : 'Campaign price') + '</th></tr></thead>';
      html += '<tbody>';
      $.each(data.variations, function (i, v) {
        var duplicate = rowExists($panel, v.variation_id, $targetRow);
        html += '<tr class="' + (duplicate ? 'is-existing' : '') + '" data-variation-id="' + v.variation_id + '" data-label="' + escapeAttr(data.parent_name + ' - ' + v.label) + '" data-woo-price="' + escapeAttr(v.woo_price) + '" data-raw-price="' + escapeAttr(v.raw_price) + '" data-stock="' + escapeAttr(v.stock) + '">';
        html += '<td><input type="checkbox" class="nowcastf-var-check" ' + (duplicate ? 'disabled' : '') + '></td>';
        html += '<td><strong>' + escapeHtml(v.label) + '</strong>' + (v.sku ? '<small>SKU: ' + escapeHtml(v.sku) + '</small>' : '') + (duplicate ? '<span class="nowcastf-existing-badge">' + escapeHtml(settings.i18n && settings.i18n.alreadyAdded ? settings.i18n.alreadyAdded : 'Already added') + '</span>' : '') + '</td>';
        html += '<td>' + v.woo_price + '</td>';
        html += '<td>' + v.stock + '</td>';
        html += '<td><input type="number" min="0.01" step="0.01" value="' + (v.raw_price || '') + '" class="nowcastf-modal-price" ' + (duplicate ? 'disabled' : '') + '></td>';
        html += '</tr>';
      });
      html += '</tbody></table></div>';
    }

    html += '<div class="nowcastf-modal-actions">';
    html += '<button type="button" class="button woo-campaign-modal-cancel">' + escapeHtml(settings.i18n && settings.i18n.cancel ? settings.i18n.cancel : 'Cancel') + '</button>';
    html += '<button type="button" class="button button-primary woo-campaign-modal-add" disabled>' + escapeHtml(settings.i18n && settings.i18n.addVariations ? settings.i18n.addVariations : 'Add selected variations') + '</button>';
    html += '</div></div></div>';

    activeModal = $(html);
    $('body').addClass('nowcastf-modal-open').append(activeModal);

    var $modal = activeModal;
    $modal.find('.woo-campaign-var-check').on('change', function () {
      var available = $modal.find('.woo-campaign-var-check:not(:disabled)');
      var checked = available.filter(':checked');
      $modal.find('.woo-campaign-modal-select-all').prop('checked', available.length > 0 && available.length === checked.length);
      refreshModalSelection($modal);
    });

    $modal.find('.woo-campaign-modal-select-all').on('change', function () {
      $modal.find('.woo-campaign-var-check:not(:disabled)').prop('checked', $(this).is(':checked')).trigger('change');
    });

    $modal.on('click', function (event) {
      if ($(event.target).is('.woo-campaign-variation-modal-overlay')) closeModal();
    });

    $modal.find('.woo-campaign-modal-cancel, .woo-campaign-modal-close').on('click', closeModal);

    $modal.find('.woo-campaign-modal-add').on('click', function () {
      var selected = [];
      $modal.find('tbody tr').each(function () {
        var $tr = $(this);
        if ($tr.find('.woo-campaign-var-check').is(':checked')) {
          selected.push({
            variationId: $tr.attr('data-variation-id'),
            label: $tr.attr('data-label'),
            wooPrice: $tr.attr('data-woo-price'),
            rawPrice: $tr.attr('data-raw-price'),
            stock: $tr.attr('data-stock'),
            price: $tr.find('.woo-campaign-modal-price').val()
          });
        }
      });

      if (!selected.length) return;

      $.each(selected, function (i, item) {
        if (rowExists($panel, item.variationId, $targetRow)) return;
        var template = $('#tmpl-woo-campaign-product-row').html();
        var $newRow = $(template);
        var $select = $newRow.find('select.wc-product-search');

        $newRow.attr('data-saleable-id', item.variationId);
        $select.append('<option value="' + item.variationId + '" selected>' + escapeHtml(item.label) + '</option>');
        $newRow.find('.woo-campaign-woo-price').html(item.wooPrice);
        $newRow.find('[data-woo-base-price]').attr('data-woo-base-price', item.rawPrice);
        $newRow.find('.woo-campaign-stock').html(item.stock);
        $newRow.find('input[name="nowcastf_price[]"]').val(item.price);
        $newRow.find('.woo-campaign-product-search-wrap').append('<span class="nowcastf-product-type">' + escapeHtml(settings.i18n && settings.i18n.variation ? settings.i18n.variation : 'Variation') + '</span>');

        $panel.find('.woo-campaign-product-rows').append($newRow);
        initProductSearch();
        updateSavings($newRow);
      });

      $targetRow.remove();
      updatePanelState($panel);
      closeModal();
    });

    refreshModalSelection($modal);
    setTimeout(function () {
      $modal.find('.woo-campaign-modal-close').trigger('focus');
    }, 0);
  }

  $(document).on('change', '.woo-campaign-product-row select.wc-product-search', function () {
    var $select = $(this);
    var productId = parseInt($select.val() || '0', 10);
    var $row = $select.closest('.woo-campaign-product-row');
    var $panel = $row.closest('.woo-campaign-products-panel');

    if (!productId || $row.data('fetching')) return;
    if (rowExists($panel, productId, $row)) {
      $select.val(null).trigger('change.select2');
      window.alert(settings.i18n && settings.i18n.alreadyAdded ? settings.i18n.alreadyAdded : 'This product is already in the campaign.');
      return;
    }

    $row.data('fetching', true).addClass('is-loading');

    $.ajax({
      url: settings.ajaxUrl,
      type: 'POST',
      data: {
        action: 'nowcastf_get_product_variations',
        nonce: settings.nonce,
        product_id: productId
      }
    }).done(function (res) {
      if (!res || !res.success) {
        window.alert(res && res.data && res.data.message ? res.data.message : 'Error fetching product info');
        return;
      }

      var data = res.data;
      if (data.is_variable) {
        openVariationPicker(data, $row, $panel);
        return;
      }

      $row.attr('data-saleable-id', data.saleable_id || productId);
      $row.find('.woo-campaign-woo-price').html(data.woo_price);
      $row.find('[data-woo-base-price]').attr('data-woo-base-price', data.raw_price || 0);
      $row.find('.woo-campaign-stock').html(data.stock);
      if (!$row.find('[data-woo-campaign-price-input]').val()) {
        $row.find('[data-woo-campaign-price-input]').val(data.raw_price || '');
      }
      updateSavings($row);
    }).fail(function () {
      window.alert(settings.i18n && settings.i18n.networkError ? settings.i18n.networkError : 'Network error fetching product information.');
    }).always(function () {
      $row.data('fetching', false).removeClass('is-loading');
    });
  });

  $(document).on('input change', '[data-woo-campaign-price-input]', function () {
    updateSavings($(this).closest('.woo-campaign-product-row'));
  });

  $(document).on('click', '.woo-campaign-add-product-row', function () {
    var template = $('#tmpl-woo-campaign-product-row').html();
    var $panel = $(this).closest('.woo-campaign-products-panel');
    var $row = $(template);
    $panel.find('.woo-campaign-product-rows').append($row);
    updatePanelState($panel);
    initProductSearch();
    setTimeout(function () {
      $row.find('.wc-product-search').select2('open');
    }, 0);
  });

  $(document).on('click', '.woo-campaign-remove-product-row', function () {
    var $panel = $(this).closest('.woo-campaign-products-panel');
    $(this).closest('.woo-campaign-product-row').remove();
    updatePanelState($panel);
  });

  $(document).on('keydown', function (event) {
    if (event.key === 'Escape' && activeModal) closeModal();
  });

  $(function () {
    $('.woo-campaign-products-panel').each(function () {
      updatePanelState($(this));
    });
    updateAllSavings(document);
  });
})(jQuery);
