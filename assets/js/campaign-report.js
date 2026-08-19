(function () {
  'use strict';

  var settings = window.NowCastfLiveReport || {};
  if (!settings.dataUrl) return;
  var numberFormat = new Intl.NumberFormat();

  function setText(selector, value) {
    var el = document.querySelector(selector);
    if (el) el.textContent = value;
  }

  function setHtml(selector, value) {
    var el = document.querySelector(selector);
    if (el) el.innerHTML = value || '';
  }

  function renderProducts(products) {
    var root = document.querySelector('[data-report-products]');
    if (!root) return;
    root.innerHTML = '';
    if (!products || !products.length) {
      var empty = document.createElement('p');
      empty.className = 'nowcastf-report-empty';
      empty.textContent = (settings.i18n && settings.i18n.emptyProducts) || 'There are no paid campaign product results yet.';
      root.appendChild(empty);
      return;
    }

    products.forEach(function (product) {
      var row = document.createElement('div');
      row.className = 'nowcastf-report-product-row';
      var info = document.createElement('div');
      var name = document.createElement('strong');
      name.textContent = product.name || '';
      var units = document.createElement('span');
      units.textContent = numberFormat.format(Number(product.net_units || 0)) + ' ' + ((settings.i18n && settings.i18n.items) || 'items');
      var sales = document.createElement('strong');
      sales.innerHTML = product.net_sales_html || '';
      info.appendChild(name);
      info.appendChild(units);
      row.appendChild(info);
      row.appendChild(sales);
      root.appendChild(row);
    });
  }

  function render(payload) {
    var summary = payload.summary || {};
    var formatted = payload.formatted || {};
    ['orders', 'units', 'pending_orders', 'refunded_units'].forEach(function (key) {
      setText('[data-report-value="' + key + '"]', numberFormat.format(Number(summary[key] || 0)));
    });
    ['campaign_subtotal', 'discount', 'refund', 'net_sales', 'average_order'].forEach(function (key) {
      setHtml('[data-report-money="' + key + '"]', formatted[key] || '');
    });
    setText('[data-report-updated]', payload.updated_label || '');
    renderProducts(payload.products || []);
  }

  function refresh() {
    if (document.hidden) return;
    fetch(settings.dataUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (response) {
        if (response.status === 401 || response.status === 404) {
          window.location.reload();
          throw new Error('Report access revoked');
        }
        if (!response.ok) throw new Error('Report refresh failed');
        return response.json();
      })
      .then(function (json) {
        if (json && json.success && json.data) render(json.data);
      })
      .catch(function () {
        // Non-auth transport errors keep the last successful snapshot visible until the next retry.
      });
  }

  window.setInterval(refresh, Number(settings.interval) || 15000);
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) refresh();
  });
})();
