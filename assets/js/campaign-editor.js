(function ($) {
  'use strict';

  var config = window.NowCastfEditor || {};
  if (!config.campaignId) return;

  var sections = Array.isArray(config.sections) ? config.sections.slice() : [];
  var products = Array.isArray(config.products) ? config.products.slice() : [];
  var layouts = config.layouts || {};
  var targetSectionKey = null;
  var pickerItems = [];
  var draggedSectionKey = null;

  function uid(prefix) {
    return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function cleanText(value) {
    var div = document.createElement('div');
    div.innerHTML = String(value || '');
    return div.textContent || div.innerText || '';
  }

  function sectionByKey(key) {
    return sections.find(function (section) { return section.clientKey === key; });
  }

  function productsForSection(key) {
    return products.filter(function (product) { return product.sectionKey === key; })
      .sort(function (a, b) { return Number(a.displayOrder || 0) - Number(b.displayOrder || 0); });
  }

  function render() {
    var $builder = $('#woo-campaign-sections-builder');
    if (!$builder.length) return;
    if (!sections.length) {
      sections.push({ id: 0, clientKey: uid('section-new'), title: '', description: '', imageId: 0, imageUrl: '', layout: 'quick_order', status: 'active', displayOrder: 0 });
    }
    $builder.html(sections.map(renderSection).join(''));
    syncOrder();
  }

  function renderSection(section, index) {
    var sectionProducts = productsForSection(section.clientKey);
    var image = section.imageUrl
      ? '<img src="' + escapeHtml(section.imageUrl) + '" alt="">'
      : '<span class="dashicons dashicons-format-image"></span>';
    var layoutHtml = Object.keys(layouts).map(function (key) {
      var item = layouts[key];
      var checked = section.layout === key ? ' checked' : '';
      return '<label class="nowcastf-layout-choice' + (checked ? ' is-selected' : '') + '">' +
        '<input type="radio" name="layout-' + escapeHtml(section.clientKey) + '" value="' + escapeHtml(key) + '"' + checked + ' data-section-layout>' +
        '<span class="nowcastf-layout-icon is-' + escapeHtml(key) + '"><i></i><i></i><i></i></span>' +
        '<strong>' + escapeHtml(item.label) + '</strong>' +
        '<small>' + escapeHtml(item.description) + '</small>' +
      '</label>';
    }).join('');

    var productsHtml = sectionProducts.length
      ? sectionProducts.map(function (product, productIndex) { return renderProduct(product, productIndex, sectionProducts.length); }).join('')
      : '<div class="nowcastf-section-empty"><span class="dashicons dashicons-products"></span><strong>' + escapeHtml(config.i18n.noProducts || 'No products') + '</strong><p>' + escapeHtml(config.i18n.chooseProductsHelp || 'Choose a simple or variable product from the WooCommerce product catalog.') + '</p></div>';

    return '<article class="nowcastf-section-block" data-section-key="' + escapeHtml(section.clientKey) + '" draggable="true">' +
      '<header class="nowcastf-section-header">' +
        '<div class="nowcastf-section-drag"><span class="dashicons dashicons-menu"></span></div>' +
        '<div class="nowcastf-section-number">Section ' + (index + 1) + '</div>' +
        '<div class="nowcastf-section-header-title"><strong>' + escapeHtml(section.title || config.i18n.untitledSection || 'Section') + '</strong><span>' + sectionProducts.length + ' items · ' + escapeHtml((layouts[section.layout] || {}).label || section.layout) + '</span></div>' +
        '<div class="nowcastf-section-header-actions">' +
          '<button type="button" class="button-link" data-section-move="up" title="' + escapeHtml(config.i18n.moveUp || 'Move up') + '"><span class="dashicons dashicons-arrow-up-alt2"></span></button>' +
          '<button type="button" class="button-link" data-section-move="down" title="' + escapeHtml(config.i18n.moveDown || 'Move down') + '"><span class="dashicons dashicons-arrow-down-alt2"></span></button>' +
          '<button type="button" class="button-link-delete" data-section-remove title="' + escapeHtml(config.i18n.deleteSection || 'Delete section') + '"><span class="dashicons dashicons-trash"></span></button>' +
        '</div>' +
      '</header>' +
      '<div class="nowcastf-section-body">' +
        '<div class="nowcastf-section-fields">' +
          '<label><span>' + escapeHtml(config.i18n.sectionTitle || 'Section title') + '</span><input type="text" value="' + escapeHtml(section.title) + '" data-section-title placeholder="' + escapeHtml(config.i18n.sectionTitlePlaceholder || 'For example: Mix and match fragrances') + '"></label>' +
          '<label class="wide"><span>' + escapeHtml(config.i18n.sectionDescription || 'Section description') + '</span><textarea rows="3" data-section-description placeholder="' + escapeHtml(config.i18n.sectionDescriptionPlaceholder || 'Describe this product section, offer, or purchase guidance.') + '">' + escapeHtml(section.description) + '</textarea></label>' +
          '<div class="nowcastf-section-image-field"><span>' + escapeHtml(config.i18n.sectionImage || 'Section image') + '</span><div class="nowcastf-section-image-preview" data-section-image-preview>' + image + '</div><div><button type="button" class="button" data-section-image-select>' + escapeHtml(config.i18n.selectImage || 'Select image') + '</button>' + (section.imageId ? ' <button type="button" class="button-link-delete" data-section-image-remove>' + escapeHtml(config.i18n.remove || 'Remove') + '</button>' : '') + '</div></div>' +
        '</div>' +
        '<div class="nowcastf-layout-picker"><div class="nowcastf-subheading"><strong>' + escapeHtml(config.i18n.productLayout || 'Product layout') + '</strong><span>' + escapeHtml(config.i18n.productLayoutHelp || 'The layout applies to the entire section to keep its presentation consistent.') + '</span></div><div class="nowcastf-layout-options">' + layoutHtml + '</div></div>' +
        '<div class="nowcastf-section-products">' +
          '<div class="nowcastf-subheading with-action"><div><strong>' + escapeHtml(config.i18n.products || 'Products') + '</strong><span>' + escapeHtml(config.i18n.productAuthorityHelp || 'Campaign items store only campaign price, campaign copy, and display status. WooCommerce remains authoritative for name, image, and stock.') + '</span></div><button type="button" class="button button-primary" data-section-add-product><span class="dashicons dashicons-plus-alt2"></span>' + escapeHtml(config.i18n.addProduct || 'Add product') + '</button></div>' +
          '<div class="nowcastf-section-product-list">' + productsHtml + '</div>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  function renderProduct(product, index, total) {
    var sectionOptions = sections.map(function (section) {
      return '<option value="' + escapeHtml(section.clientKey) + '"' + (section.clientKey === product.sectionKey ? ' selected' : '') + '>' + escapeHtml(section.title || config.i18n.untitledSection || 'Section') + '</option>';
    }).join('');
    var image = product.image ? '<img src="' + escapeHtml(product.image) + '" alt="">' : '<span class="dashicons dashicons-format-image"></span>';
    var variation = product.variationName ? '<span class="nowcastf-product-variation">' + escapeHtml(product.variationName) + '</span>' : '<span class="nowcastf-product-variation">Simple product</span>';
    var sku = product.sku ? '<code>SKU ' + escapeHtml(product.sku) + '</code>' : '';
    return '<div class="nowcastf-editor-product" data-saleable-id="' + Number(product.saleableId) + '">' +
      '<div class="nowcastf-editor-product-main">' +
        '<div class="nowcastf-editor-product-image">' + image + '</div>' +
        '<div class="nowcastf-editor-product-title"><strong>' + escapeHtml(product.productName) + '</strong>' + variation + sku + '</div>' +
        '<div class="nowcastf-editor-product-reference"><span>Woo price</span><strong>' + (product.wooPriceHtml || '') + '</strong><div class="nowcastf-stock-html">' + (product.stockHtml || '') + '</div></div>' +
      '</div>' +
      '<div class="nowcastf-editor-product-fields">' +
        '<label><span>Campaign Price</span><input type="text" class="wc_input_price" inputmode="decimal" value="' + escapeHtml(product.campaignPrice) + '" data-product-price required><small data-saving-preview>' + savingsText(product.wooPrice, product.campaignPrice) + '</small></label>' +
        '<label><span>' + escapeHtml(config.i18n.displayStatus || 'Display status') + '</span><select data-product-status><option value="active"' + (product.status === 'active' ? ' selected' : '') + '>' + escapeHtml(config.i18n.visible || 'Visible') + '</option><option value="paused"' + (product.status === 'paused' ? ' selected' : '') + '>' + escapeHtml(config.i18n.hidden || 'Hidden') + '</option></select></label>' +
        '<label class="wide"><span>' + escapeHtml(config.i18n.campaignProductCopy || 'Campaign product copy') + '</span><textarea rows="2" data-product-copy placeholder="' + escapeHtml(config.i18n.campaignProductCopyPlaceholder || 'Used only in this campaign and does not change the WooCommerce product description.') + '">' + escapeHtml(product.campaignCopy || '') + '</textarea></label>' +
      '</div>' +
      '<div class="nowcastf-editor-product-footer">' +
        '<label>' + escapeHtml(config.i18n.moveTo || 'Move to') + ' <select data-product-section>' + sectionOptions + '</select></label>' +
        '<div><button type="button" class="button-link" data-product-move="up"' + (index === 0 ? ' disabled' : '') + '><span class="dashicons dashicons-arrow-up-alt2"></span></button><button type="button" class="button-link" data-product-move="down"' + (index === total - 1 ? ' disabled' : '') + '><span class="dashicons dashicons-arrow-down-alt2"></span></button><button type="button" class="button-link-delete" data-product-remove><span class="dashicons dashicons-trash"></span> ' + escapeHtml(config.i18n.remove || 'Remove') + '</button></div>' +
      '</div>' +
    '</div>';
  }

  function savingsText(base, campaign) {
    base = Number(base || 0);
    campaign = Number(campaign || 0);
    if (!base || !campaign || campaign >= base) return '';
    return 'Save ' + Math.round((1 - campaign / base) * 100) + '%';
  }

  function syncOrder() {
    sections.forEach(function (section, sectionIndex) {
      section.displayOrder = sectionIndex;
      var sectionProducts = productsForSection(section.clientKey);
      sectionProducts.forEach(function (product, productIndex) { product.displayOrder = productIndex; });
    });
  }

  function addSection() {
    sections.push({ id: 0, clientKey: uid('section-new'), title: '', description: '', imageId: 0, imageUrl: '', layout: 'quick_order', status: 'active', displayOrder: sections.length });
    render();
  }

  function removeSection(key) {
    if (sections.length <= 1) return;
    if (!window.confirm(config.i18n.removeSectionConfirm || 'Remove section?')) return;
    var fallback = sections.find(function (section) { return section.clientKey !== key; });
    products.forEach(function (product) { if (product.sectionKey === key) product.sectionKey = fallback.clientKey; });
    sections = sections.filter(function (section) { return section.clientKey !== key; });
    render();
  }

  function moveSection(key, delta) {
    var index = sections.findIndex(function (section) { return section.clientKey === key; });
    var next = index + delta;
    if (index < 0 || next < 0 || next >= sections.length) return;
    var tmp = sections[index]; sections[index] = sections[next]; sections[next] = tmp;
    render();
  }

  function moveProduct(saleableId, sectionKey, delta) {
    var sectionProducts = productsForSection(sectionKey);
    var index = sectionProducts.findIndex(function (product) { return Number(product.saleableId) === Number(saleableId); });
    var next = index + delta;
    if (index < 0 || next < 0 || next >= sectionProducts.length) return;
    var a = sectionProducts[index];
    var b = sectionProducts[next];
    var order = a.displayOrder; a.displayOrder = b.displayOrder; b.displayOrder = order;
    render();
  }

  function openProductModal(sectionKey) {
    targetSectionKey = sectionKey;
    pickerItems = [];
    var $modal = $('[data-woo-campaign-product-modal]');
    $modal.prop('hidden', false);
    $('body').addClass('nowcastf-editor-modal-open');
    var $search = $modal.find('[data-woo-campaign-product-search]');
    $search.empty().val(null).trigger('change');
    $modal.find('[data-woo-campaign-product-picker-result]').html('<div class="nowcastf-picker-placeholder"><span class="dashicons dashicons-search"></span><p>' + escapeHtml(config.i18n.searchProductHelp || 'Search for a WooCommerce product. Variable products list their variations.') + '</p></div>');
    $modal.find('[data-woo-campaign-product-confirm]').prop('disabled', true);
    $(document.body).trigger('wc-enhanced-select-init');
  }

  function closeProductModal() {
    $('[data-woo-campaign-product-modal]').prop('hidden', true);
    $('body').removeClass('nowcastf-editor-modal-open');
    targetSectionKey = null;
    pickerItems = [];
  }

  function fetchProductDetails(productId) {
    var $result = $('[data-woo-campaign-product-picker-result]');
    $result.html('<div class="nowcastf-picker-loading"><span class="spinner is-active"></span>' + escapeHtml(config.i18n.loadingProduct || 'Loading product data…') + '</div>');
    $.post(config.ajaxUrl, { action: 'nowcastf_editor_product_details', nonce: config.nonce, product_id: productId })
      .done(function (response) {
        if (!response || !response.success) {
          $result.html('<div class="notice notice-error inline"><p>' + escapeHtml(response && response.data && response.data.message ? response.data.message : 'Unable to load product.') + '</p></div>');
          return;
        }
        pickerItems = response.data.items || [];
        renderPicker(response.data);
      })
      .fail(function () { $result.html('<div class="notice notice-error inline"><p>Network error.</p></div>'); });
  }

  function renderPicker(data) {
    var existing = new Set(products.map(function (product) { return Number(product.saleableId); }));
    var items = (data.items || []).map(function (item) {
      var isExisting = existing.has(Number(item.saleableId));
      var disabled = isExisting ? ' disabled' : '';
      return '<label class="nowcastf-picker-item' + (isExisting ? ' is-existing' : '') + '">' +
        '<input type="checkbox" data-picker-item value="' + Number(item.saleableId) + '"' + disabled + '>' +
        '<span class="nowcastf-picker-item-image">' + (item.image ? '<img src="' + escapeHtml(item.image) + '" alt="">' : '<span class="dashicons dashicons-format-image"></span>') + '</span>' +
        '<span class="nowcastf-picker-item-name"><strong>' + escapeHtml(item.variationName || item.productName) + '</strong><small>' + (item.sku ? 'SKU ' + escapeHtml(item.sku) : (data.type === 'simple' ? 'Simple product' : 'Variation')) + '</small></span>' +
        '<span class="nowcastf-picker-item-stock">' + (item.stockHtml || '') + '</span>' +
        '<span class="nowcastf-picker-item-price">' + (item.wooPriceHtml || '') + '</span>' +
        '<label class="nowcastf-picker-campaign-price">Campaign Price<input type="text" class="wc_input_price" inputmode="decimal" value="' + escapeHtml(item.wooPrice) + '" data-picker-price' + disabled + '></label>' +
        (isExisting ? '<span class="nowcastf-picker-existing">Already added</span>' : '') +
      '</label>';
    }).join('');
    $('[data-woo-campaign-product-picker-result]').html(
      '<div class="nowcastf-picker-product-head"><div>' + (data.product.image ? '<img src="' + escapeHtml(data.product.image) + '" alt="">' : '') + '<div><span>' + (data.type === 'variable' ? 'Variable product' : 'Simple product') + '</span><h3>' + escapeHtml(data.product.name) + '</h3></div></div>' + (data.type === 'variable' ? '<button type="button" class="button-link" data-picker-select-all>' + escapeHtml(config.i18n.selectAllVariations || 'Select all available variations') + '</button>' : '') + '</div>' +
      '<div class="nowcastf-picker-items">' + items + '</div>'
    );
    if (data.type === 'simple') {
      var $single = $('[data-picker-item]:not(:disabled)').first();
      $single.prop('checked', true);
    }
    updatePickerConfirm();
  }

  function updatePickerConfirm() {
    var has = $('[data-picker-item]:checked:not(:disabled)').length > 0;
    $('[data-woo-campaign-product-confirm]').prop('disabled', !has);
  }

  function confirmPicker() {
    if (!targetSectionKey) return;
    $('[data-picker-item]:checked:not(:disabled)').each(function () {
      var saleableId = Number($(this).val());
      var item = pickerItems.find(function (candidate) { return Number(candidate.saleableId) === saleableId; });
      if (!item) return;
      if (products.some(function (product) { return Number(product.saleableId) === saleableId; })) return;
      var price = $(this).closest('.woo-campaign-picker-item').find('[data-picker-price]').val();
      products.push({
        id: 0,
        saleableId: saleableId,
        productId: Number(item.productId),
        variationId: Number(item.variationId || 0),
        sectionKey: targetSectionKey,
        productName: item.productName,
        variationName: item.variationName || '',
        sku: item.sku || '',
        image: item.image || '',
        wooPrice: Number(item.wooPrice || 0),
        wooPriceHtml: item.wooPriceHtml || '',
        stockHtml: item.stockHtml || '',
        campaignPrice: price || item.wooPrice,
        campaignCopy: '',
        status: 'active',
        displayOrder: productsForSection(targetSectionKey).length
      });
    });
    closeProductModal();
    render();
  }

  function selectSectionImage(sectionKey) {
    var section = sectionByKey(sectionKey);
    if (!section || !window.wp || !wp.media) return;
    var frame = wp.media({ title: config.i18n.selectSectionImage || 'Select section image', button: { text: config.i18n.useImage || 'Use this image' }, multiple: false, library: { type: 'image' } });
    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      section.imageId = Number(attachment.id || 0);
      section.imageUrl = attachment.sizes && attachment.sizes.medium_large ? attachment.sizes.medium_large.url : attachment.url;
      render();
    });
    frame.open();
  }

  function serialize() {
    syncOrder();
    $('#woo-campaign-sections-json').val(JSON.stringify(sections.map(function (section, index) {
      return {
        id: Number(section.id || 0),
        client_key: section.clientKey,
        title: section.title || '',
        description: section.description || '',
        image_id: Number(section.imageId || 0),
        layout: section.layout || 'quick_order',
        status: section.status || 'active',
        display_order: index
      };
    })));
    var serializedProducts = [];
    sections.forEach(function (section) {
      productsForSection(section.clientKey).forEach(function (product, index) {
        serializedProducts.push({
          saleable_id: Number(product.saleableId),
          section_key: section.clientKey,
          campaign_price: String(product.campaignPrice || ''),
          campaign_copy: product.campaignCopy || '',
          status: product.status || 'active',
          display_order: index
        });
      });
    });
    $('#woo-campaign-products-json').val(JSON.stringify(serializedProducts));
  }

  $(document).on('click', '[data-woo-campaign-add-section]', addSection);

  $(document).on('input', '[data-section-title]', function () {
    var key = $(this).closest('[data-section-key]').attr('data-section-key');
    var section = sectionByKey(key); if (section) section.title = this.value;
    $(this).closest('.woo-campaign-section-block').find('.woo-campaign-section-header-title strong').first().text(this.value || config.i18n.untitledSection);
  });
  $(document).on('input', '[data-section-description]', function () { var section = sectionByKey($(this).closest('[data-section-key]').attr('data-section-key')); if (section) section.description = this.value; });
  $(document).on('change', '[data-section-layout]', function () { var section = sectionByKey($(this).closest('[data-section-key]').attr('data-section-key')); if (section) section.layout = this.value; render(); });
  $(document).on('click', '[data-section-image-select]', function () { selectSectionImage($(this).closest('[data-section-key]').attr('data-section-key')); });
  $(document).on('click', '[data-section-image-remove]', function () { var section = sectionByKey($(this).closest('[data-section-key]').attr('data-section-key')); if (section) { section.imageId = 0; section.imageUrl = ''; render(); } });
  $(document).on('click', '[data-section-remove]', function () { removeSection($(this).closest('[data-section-key]').attr('data-section-key')); });
  $(document).on('click', '[data-section-move]', function () { moveSection($(this).closest('[data-section-key]').attr('data-section-key'), $(this).attr('data-section-move') === 'up' ? -1 : 1); });
  $(document).on('click', '[data-section-add-product]', function () { openProductModal($(this).closest('[data-section-key]').attr('data-section-key')); });

  $(document).on('input', '[data-product-price]', function () {
    var saleableId = Number($(this).closest('[data-saleable-id]').attr('data-saleable-id'));
    var product = products.find(function (item) { return Number(item.saleableId) === saleableId; });
    if (product) { product.campaignPrice = this.value; $(this).siblings('[data-saving-preview]').text(savingsText(product.wooPrice, this.value)); }
  });
  $(document).on('input', '[data-product-copy]', function () { var id = Number($(this).closest('[data-saleable-id]').attr('data-saleable-id')); var product = products.find(function (item) { return Number(item.saleableId) === id; }); if (product) product.campaignCopy = this.value; });
  $(document).on('change', '[data-product-status]', function () { var id = Number($(this).closest('[data-saleable-id]').attr('data-saleable-id')); var product = products.find(function (item) { return Number(item.saleableId) === id; }); if (product) product.status = this.value; });
  $(document).on('change', '[data-product-section]', function () { var id = Number($(this).closest('[data-saleable-id]').attr('data-saleable-id')); var product = products.find(function (item) { return Number(item.saleableId) === id; }); if (product) { product.sectionKey = this.value; product.displayOrder = productsForSection(this.value).length; render(); } });
  $(document).on('click', '[data-product-remove]', function () { var id = Number($(this).closest('[data-saleable-id]').attr('data-saleable-id')); products = products.filter(function (item) { return Number(item.saleableId) !== id; }); render(); });
  $(document).on('click', '[data-product-move]', function () { var $row = $(this).closest('[data-saleable-id]'); moveProduct(Number($row.attr('data-saleable-id')), $row.closest('[data-section-key]').attr('data-section-key'), $(this).attr('data-product-move') === 'up' ? -1 : 1); });

  $(document).on('change', '[data-woo-campaign-product-search]', function () { var id = Number($(this).val() || 0); if (id) fetchProductDetails(id); });
  $(document).on('change', '[data-picker-item]', updatePickerConfirm);
  $(document).on('click', '[data-picker-select-all]', function () { $('[data-picker-item]:not(:disabled)').prop('checked', true); updatePickerConfirm(); });
  $(document).on('click', '[data-woo-campaign-product-confirm]', confirmPicker);
  $(document).on('click', '[data-woo-campaign-product-modal-close]', closeProductModal);
  $(document).on('keydown', function (event) { if (event.key === 'Escape' && !$('[data-woo-campaign-product-modal]').prop('hidden')) closeProductModal(); });

  $(document).on('dragstart', '[data-section-key]', function (event) { draggedSectionKey = $(this).attr('data-section-key'); $(this).addClass('is-dragging'); event.originalEvent.dataTransfer.effectAllowed = 'move'; });
  $(document).on('dragend', '[data-section-key]', function () { draggedSectionKey = null; $('[data-section-key]').removeClass('is-dragging is-drag-over'); });
  $(document).on('dragover', '[data-section-key]', function (event) { if (!draggedSectionKey) return; event.preventDefault(); $(this).addClass('is-drag-over'); });
  $(document).on('dragleave', '[data-section-key]', function () { $(this).removeClass('is-drag-over'); });
  $(document).on('drop', '[data-section-key]', function (event) {
    event.preventDefault();
    var targetKey = $(this).attr('data-section-key');
    $(this).removeClass('is-drag-over');
    if (!draggedSectionKey || draggedSectionKey === targetKey) return;
    var from = sections.findIndex(function (section) { return section.clientKey === draggedSectionKey; });
    var to = sections.findIndex(function (section) { return section.clientKey === targetKey; });
    if (from < 0 || to < 0) return;
    var moved = sections.splice(from, 1)[0]; sections.splice(to, 0, moved); render();
  });

  $('#woo-campaign-editor-form').on('submit', serialize);

  render();
  $(document.body).trigger('wc-enhanced-select-init');
})(jQuery);
