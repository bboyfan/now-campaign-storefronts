(function () {
  'use strict';

  var settings = window.NowCastfSettings || {};
  var i18n = settings.i18n || {};
  var toastTimer = null;

  function post(action, payload) {
    var body = new URLSearchParams(Object.assign({ action: action, nonce: settings.nonce }, payload || {}));
    return fetch(settings.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (response) { return response.json(); }).then(function (json) {
      if (!json || !json.success) {
        var message = json && json.data && json.data.message ? json.data.message : i18n.error || 'Error';
        throw new Error(message);
      }
      return json.data;
    });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>'"]/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
    });
  }

  function formatSaving(base, campaign) {
    base = Number(base || 0); campaign = Number(campaign || 0);
    if (!base || !campaign || campaign >= base) return '';
    return String(i18n.save || 'Save %d%%').replace('%d', Math.round((1 - campaign / base) * 100));
  }

  function formatSelectedTotal(value) {
    try {
      return new Intl.NumberFormat(document.documentElement.lang || 'zh-TW', { style: 'currency', currency: 'TWD', maximumFractionDigits: 0 }).format(value || 0);
    } catch (error) {
      return 'NT$' + Math.round(value || 0).toLocaleString();
    }
  }

  function itemMarkup(item) {
    var image = item.image ? '<img src="' + escapeHtml(item.image) + '" alt="">' : '<span class="nowcastf-mini-cart-image-placeholder" aria-hidden="true"></span>';
    var badge = item.campaign ? '<span class="nowcastf-badge">' + escapeHtml(i18n.campaign || 'Campaign') + '</span>' : '';
    return '<div class="nowcastf-mini-cart-item' + (item.campaign ? ' is-campaign' : '') + '" data-cart-item-key="' + escapeHtml(item.key) + '">' +
      '<div class="nowcastf-mini-cart-item-image">' + image + '</div>' +
      '<div class="nowcastf-mini-cart-item-main"><div class="nowcastf-mini-cart-item-title"><strong>' + escapeHtml(item.name) + '</strong>' + badge + '</div>' +
      '<div class="nowcastf-mini-cart-item-controls"><div class="nowcastf-cart-quantity"><button type="button" data-woo-campaign-cart-step="-1" aria-label="' + escapeHtml(i18n.decreaseQty || 'Decrease quantity') + '">−</button><input type="number" min="0" step="1" value="' + Number(item.quantity) + '" data-woo-campaign-cart-qty aria-label="' + escapeHtml(i18n.quantity || 'Quantity') + '"><button type="button" data-woo-campaign-cart-step="1" aria-label="' + escapeHtml(i18n.increaseQty || 'Increase quantity') + '">+</button></div><button type="button" class="nowcastf-remove-link" data-woo-campaign-cart-remove>' + escapeHtml(i18n.remove || 'Remove') + '</button></div></div>' +
      '<div class="nowcastf-mini-cart-item-total">' + (item.lineTotal || '') + '</div></div>';
  }

  function emptyMarkup() {
    return '<div class="nowcastf-cart-empty"><span class="dashicons dashicons-cart"></span><strong>' + escapeHtml(i18n.empty || 'Your cart is empty') + '</strong><p>' + escapeHtml(i18n.emptyHelp || 'Add a campaign item to get started.') + '</p></div>';
  }

  function renderMiniCart(snapshot) {
    document.querySelectorAll('[data-woo-campaign-mini-cart]').forEach(function (root) {
      root.classList.toggle('has-items', Number(snapshot.count) > 0);
      root.classList.toggle('is-empty', Number(snapshot.count) === 0);
      root.querySelectorAll('[data-woo-campaign-cart-count]').forEach(function (el) { el.textContent = snapshot.count; });
      root.querySelectorAll('[data-woo-campaign-cart-total]').forEach(function (el) { el.innerHTML = snapshot.total || ''; });
      root.querySelectorAll('[data-woo-campaign-cart-subtotal]').forEach(function (el) { el.innerHTML = snapshot.subtotal || ''; });
      root.querySelectorAll('[data-woo-campaign-cart-discount]').forEach(function (el) { el.innerHTML = snapshot.discount || ''; });
      var list = root.querySelector('[data-woo-campaign-cart-items]');
      if (list) list.innerHTML = snapshot.items && snapshot.items.length ? snapshot.items.map(itemMarkup).join('') : emptyMarkup();
    });
    if (window.jQuery) {
      window.jQuery(document.body).trigger('updated_cart_totals');
    }
  }

  function applyWooAddToCart(data, button) {
    if (!data) return;
    var fragments = data.fragments || null;
    var cartHash = data.cart_hash || '';

    if (window.jQuery) {
      var $ = window.jQuery;
      if (fragments) {
        $.each(fragments, function (key, value) {
          $(key).replaceWith(value);
        });
      }
      var $button = button ? $(button) : false;
      $(document.body).trigger('added_to_cart', [fragments, cartHash, $button]);
    }
  }

  function setPanel(root, open) {
    if (!root) return;
    var panel = root.querySelector('[data-woo-campaign-mini-panel]');
    var toggle = root.querySelector('[data-woo-campaign-mini-toggle]');
    var chevron = root.querySelector('[data-woo-campaign-cart-chevron]');
    if (!panel || !toggle) return;
    panel.hidden = !open; root.classList.toggle('is-open', open); toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (chevron) chevron.classList.toggle('is-open', open);
  }

  function showToast(message) {
    var toast = document.querySelector('[data-woo-campaign-toast]');
    if (!toast) {
      toast = document.createElement('div'); toast.className = 'nowcastf-toast'; toast.setAttribute('data-woo-campaign-toast', ''); toast.setAttribute('role', 'status'); toast.setAttribute('aria-live', 'polite'); document.body.appendChild(toast);
    }
    toast.textContent = message; toast.classList.add('is-visible'); clearTimeout(toastTimer); toastTimer = setTimeout(function () { toast.classList.remove('is-visible'); }, 2200);
  }

  function setBusy(button, busy) {
    if (!button) return;
    button.disabled = busy; button.classList.toggle('is-loading', busy); button.setAttribute('aria-busy', busy ? 'true' : 'false');
  }

  function setProductFeedback(card, message) {
    var feedback = card && card.querySelector('[data-woo-campaign-product-feedback]');
    if (!feedback) return;
    feedback.textContent = message || ''; feedback.classList.toggle('is-visible', !!message);
  }

  function clampInput(input, min) {
    var value = parseInt(input.value || String(min), 10); if (isNaN(value)) value = min; value = Math.max(min, value); input.value = value; return value;
  }

  function updateDirectGroup(group) {
    if (!group) return;
    var count = 0; var total = 0;
    group.querySelectorAll('[data-campaign-product-option]').forEach(function (option) {
      var input = option.querySelector('[data-woo-campaign-direct-qty]');
      if (!input) return;
      var quantity = clampInput(input, 0); var price = Number(option.getAttribute('data-campaign-price') || 0);
      count += quantity; total += quantity * price;
      var quantityBox = input.closest('.woo-campaign-quantity'); if (quantityBox) quantityBox.classList.toggle('is-zero', quantity === 0);
    });
    var countEl = group.querySelector('[data-woo-campaign-selected-count]'); if (countEl) countEl.textContent = count;
    var totalEl = group.querySelector('[data-woo-campaign-selected-total]'); if (totalEl) totalEl.textContent = formatSelectedTotal(total);
    var button = group.querySelector('.woo-campaign-add-selected'); if (button && !button.classList.contains('is-loading')) button.disabled = count <= 0;
  }

  function resetDirectGroup(group) {
    group.querySelectorAll('[data-woo-campaign-direct-qty]').forEach(function (input) { input.value = 0; });
    updateDirectGroup(group);
  }

  function updateCartQuantity(row, quantity, message) {
    if (!row) return;
    row.classList.add('is-updating');
    post('nowcastf_update_cart', { cart_item_key: row.getAttribute('data-cart-item-key'), quantity: quantity }).then(function (snapshot) {
      renderMiniCart(snapshot); if (message) showToast(message);
    }).catch(function (error) { showToast(error.message); }).finally(function () { row.classList.remove('is-updating'); });
  }

  document.addEventListener('click', function (event) {
    var directStep = event.target.closest('[data-woo-campaign-direct-step]');
    if (directStep) {
      event.preventDefault();
      var option = directStep.closest('[data-campaign-product-option]'); var directQty = option && option.querySelector('[data-woo-campaign-direct-qty]');
      if (directQty) { var d = parseInt(directStep.getAttribute('data-woo-campaign-direct-step') || '0', 10); directQty.value = Math.max(0, parseInt(directQty.value || '0', 10) + d); updateDirectGroup(directStep.closest('[data-campaign-order-group]')); }
      return;
    }

    var addSelected = event.target.closest('.woo-campaign-add-selected');
    if (addSelected) {
      event.preventDefault();
      var group = addSelected.closest('[data-campaign-order-group]'); if (!group) return;
      var items = [];
      group.querySelectorAll('[data-campaign-product-option]').forEach(function (option) {
        var input = option.querySelector('[data-woo-campaign-direct-qty]'); var quantity = input ? clampInput(input, 0) : 0;
        if (quantity > 0) items.push({ campaign_product_id: Number(option.getAttribute('data-campaign-product-id')), quantity: quantity });
      });
      if (!items.length) return;
      setBusy(addSelected, true); setProductFeedback(group, '');
      post('nowcastf_add_many_cart', { campaign_id: addSelected.getAttribute('data-campaign-id'), items: JSON.stringify(items) }).then(function (data) {
        var snapshot = data && data.snapshot ? data.snapshot : data;
        renderMiniCart(snapshot);
        applyWooAddToCart(data, addSelected);
        resetDirectGroup(group);
        setProductFeedback(group, i18n.added || 'Added to cart');
        showToast(i18n.added || 'Added to cart');
      }).catch(function (error) { setProductFeedback(group, error.message); showToast(error.message); }).finally(function () { setBusy(addSelected, false); updateDirectGroup(group); });
      return;
    }

    var productStep = event.target.closest('[data-woo-campaign-product-step]');
    if (productStep) {
      event.preventDefault();
      var productCard = productStep.closest('[data-campaign-product-card], [data-campaign-product-option]'); var productQty = productCard && productCard.querySelector('[data-woo-campaign-qty]');
      if (productQty) { var delta = parseInt(productStep.getAttribute('data-woo-campaign-product-step') || '0', 10); productQty.value = Math.max(1, parseInt(productQty.value || '1', 10) + delta); }
      return;
    }

    var add = event.target.closest('.woo-campaign-add-to-cart');
    if (add) {
      event.preventDefault();
      var card = add.closest('[data-campaign-product-card], [data-campaign-product-option]'); var select = card && card.querySelector('[data-woo-campaign-variation]'); var qty = card && card.querySelector('[data-woo-campaign-qty]'); var campaignProductId = add.getAttribute('data-campaign-product-id') || (select ? select.value : '');
      setBusy(add, true); setProductFeedback(card, '');
      post('nowcastf_add_cart', { campaign_id: add.getAttribute('data-campaign-id'), campaign_product_id: campaignProductId, quantity: qty ? clampInput(qty, 1) : 1 }).then(function (data) {
        var snapshot = data && data.snapshot ? data.snapshot : data;
        renderMiniCart(snapshot);
        applyWooAddToCart(data, add);
        setProductFeedback(card, i18n.added || 'Added to cart');
        showToast(i18n.added || 'Added to cart');
      }).catch(function (error) { setProductFeedback(card, error.message); showToast(error.message); }).finally(function () { setBusy(add, false); });
      return;
    }

    var toggle = event.target.closest('[data-woo-campaign-mini-toggle]');
    if (toggle) { event.preventDefault(); var root = toggle.closest('[data-woo-campaign-mini-cart]'); setPanel(root, toggle.getAttribute('aria-expanded') !== 'true'); return; }
    var close = event.target.closest('[data-woo-campaign-mini-close]');
    if (close) { event.preventDefault(); setPanel(close.closest('[data-woo-campaign-mini-cart]'), false); return; }
    var cartStep = event.target.closest('[data-woo-campaign-cart-step]');
    if (cartStep) {
      event.preventDefault(); var cartRow = cartStep.closest('[data-cart-item-key]'); var cartQty = cartRow && cartRow.querySelector('[data-woo-campaign-cart-qty]');
      if (cartQty) { var cartDelta = parseInt(cartStep.getAttribute('data-woo-campaign-cart-step') || '0', 10); var next = Math.max(0, parseInt(cartQty.value || '0', 10) + cartDelta); cartQty.value = next; updateCartQuantity(cartRow, next, i18n.updated || 'Cart updated'); }
      return;
    }
    var remove = event.target.closest('[data-woo-campaign-cart-remove]');
    if (remove) {
      event.preventDefault(); var row = remove.closest('[data-cart-item-key]'); if (!row) return; row.classList.add('is-updating');
      post('nowcastf_remove_cart', { cart_item_key: row.getAttribute('data-cart-item-key') }).then(function (snapshot) { renderMiniCart(snapshot); showToast(i18n.removed || 'Item removed'); }).catch(function (error) { showToast(error.message); });
    }
  });

  document.addEventListener('input', function (event) {
    if (event.target.matches('[data-woo-campaign-direct-qty]')) updateDirectGroup(event.target.closest('[data-campaign-order-group]'));
  });

  document.addEventListener('change', function (event) {
    if (event.target.matches('[data-woo-campaign-variation]')) {
      var option = event.target.options[event.target.selectedIndex]; var card = event.target.closest('[data-campaign-product-card]'); var current = card && card.querySelector('[data-woo-campaign-price-current]'); var base = card && card.querySelector('[data-woo-campaign-base-price-current]'); var saving = card && card.querySelector('[data-woo-campaign-saving-current]');
      if (current && option) current.innerHTML = option.getAttribute('data-price-html') || '';
      if (base && option) { var baseValue = Number(option.getAttribute('data-base-price') || 0); var campaignValue = Number(option.getAttribute('data-campaign-price') || 0); base.innerHTML = option.getAttribute('data-base-price-html') || ''; base.hidden = !baseValue || baseValue === campaignValue; }
      if (saving && option) saving.textContent = formatSaving(option.getAttribute('data-base-price'), option.getAttribute('data-campaign-price')); setProductFeedback(card, ''); return;
    }
    if (event.target.matches('[data-woo-campaign-direct-qty]')) { clampInput(event.target, 0); updateDirectGroup(event.target.closest('[data-campaign-order-group]')); return; }
    if (event.target.matches('[data-woo-campaign-qty]')) { clampInput(event.target, 1); return; }
    if (event.target.matches('[data-woo-campaign-cart-qty]')) { var row = event.target.closest('[data-cart-item-key]'); updateCartQuantity(row, clampInput(event.target, 0), i18n.updated || 'Cart updated'); }
  });

  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') document.querySelectorAll('[data-woo-campaign-mini-cart].is-open').forEach(function (root) { setPanel(root, false); }); });

  document.querySelectorAll('[data-campaign-order-group]').forEach(updateDirectGroup);
})();
