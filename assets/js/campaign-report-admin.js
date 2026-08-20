(function ($) {
  'use strict';

  var cfg = window.BboyfanNowCastfReportAdmin || {};
  if (!cfg.campaignId) return;

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (typeof text === 'string') node.textContent = text;
    return node;
  }

  function copyText(value) {
    if (!value) return Promise.reject(new Error('Nothing to copy'));
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(value);
    }
    return new Promise(function (resolve, reject) {
      var helper = document.createElement('textarea');
      helper.value = value;
      helper.setAttribute('readonly', 'readonly');
      helper.style.position = 'fixed';
      helper.style.opacity = '0';
      document.body.appendChild(helper);
      helper.select();
      try {
        document.execCommand('copy') ? resolve() : reject(new Error('Copy failed'));
      } catch (error) {
        reject(error);
      }
      document.body.removeChild(helper);
    });
  }

  function enhanceMetrics() {
    var root = document.querySelector('.woo-campaign-editor-metrics');
    if (!root || root.dataset.reportEnhanced === '1') return;
    root.dataset.reportEnhanced = '1';
    [
      ['Average order', cfg.summary.averageOrder || ''],
      ['Pending orders', String(cfg.summary.pendingOrders || 0)],
      ['Campaign subtotal', cfg.summary.campaignSubtotal || ''],
      ['Refunded units', String(cfg.summary.refundedUnits || 0)]
    ].forEach(function (item) {
      var box = el('div');
      box.appendChild(el('span', '', item[0]));
      var strong = el('strong');
      strong.innerHTML = item[1];
      box.appendChild(strong);
      root.appendChild(box);
    });
  }

  function buildCard() {
    var sidebar = document.querySelector('.woo-campaign-editor-sidebar');
    if (!sidebar || document.querySelector('[data-woo-campaign-report-admin]')) return;

    var state = $.extend({}, cfg.share || {});
    var passwordDirty = false;
    var card = el('section', 'nowcastf-editor-card woo-campaign-report-admin-card');
    card.setAttribute('data-woo-campaign-report-admin', '1');

    var heading = el('div', 'nowcastf-editor-card-heading compact');
    var headingInner = el('div');
    headingInner.appendChild(el('span', 'nowcastf-editor-eyebrow', cfg.i18n.eyebrow));
    headingInner.appendChild(el('h2', '', cfg.i18n.title));
    heading.appendChild(headingInner);
    card.appendChild(heading);

    var enabledLabel = el('label', 'nowcastf-report-toggle');
    var enabled = document.createElement('input');
    enabled.type = 'checkbox';
    enabled.checked = !!state.enabled;
    enabledLabel.appendChild(enabled);
    enabledLabel.appendChild(el('span', '', cfg.i18n.enabled));
    card.appendChild(enabledLabel);

    var linkWrap = el('div', 'nowcastf-report-admin-field');
    linkWrap.appendChild(el('span', '', cfg.i18n.link));
    var linkRow = el('div', 'nowcastf-report-link-row');
    var linkInput = document.createElement('input');
    linkInput.type = 'text';
    linkInput.readOnly = true;
    linkRow.appendChild(linkInput);
    var copy = el('button', 'button', cfg.i18n.copy);
    copy.type = 'button';
    linkRow.appendChild(copy);
    var open = el('a', 'button', cfg.i18n.open);
    open.target = '_blank';
    open.rel = 'noopener';
    linkRow.appendChild(open);
    linkWrap.appendChild(linkRow);
    card.appendChild(linkWrap);

    var passwordWrap = el('div', 'nowcastf-report-admin-field');
    passwordWrap.appendChild(el('span', '', cfg.i18n.password));
    var passwordRow = el('div', 'nowcastf-report-password-row');
    var password = document.createElement('input');
    password.type = 'text';
    password.autocomplete = 'off';
    password.spellcheck = false;
    password.setAttribute('aria-label', cfg.i18n.password);
    passwordRow.appendChild(password);
    var copyPassword = el('button', 'button', cfg.i18n.copyPassword);
    copyPassword.type = 'button';
    passwordRow.appendChild(copyPassword);
    passwordWrap.appendChild(passwordRow);
    var passwordHelp = el('small');
    passwordWrap.appendChild(passwordHelp);
    card.appendChild(passwordWrap);

    var notice = el('div', 'nowcastf-report-admin-notice');
    notice.hidden = true;
    card.appendChild(notice);

    var actions = el('div', 'nowcastf-report-admin-actions');
    var save = el('button', 'button button-primary', cfg.i18n.save);
    save.type = 'button';
    var regenerate = el('button', 'button', cfg.i18n.regenerate);
    regenerate.type = 'button';
    actions.appendChild(save);
    actions.appendChild(regenerate);
    card.appendChild(actions);
    card.appendChild(el('p', 'description', cfg.i18n.regenerateHelp));

    function showNotice(message, isError) {
      notice.hidden = !message;
      notice.textContent = message || '';
      notice.classList.toggle('is-error', !!isError);
      notice.classList.toggle('is-success', !isError && !!message);
    }

    function applyState(next) {
      state = $.extend({}, state, next || {});
      enabled.checked = !!state.enabled;
      linkInput.value = state.url || '';
      open.href = state.url || '#';
      var shareAvailable = !!state.url && !!state.enabled;
      open.classList.toggle('disabled', !shareAvailable);
      copy.disabled = !shareAvailable;
      if (typeof state.password === 'string') password.value = state.password;
      copyPassword.disabled = !password.value;
      passwordHelp.textContent = state.password_set && !state.password_recoverable
        ? cfg.i18n.passwordLegacyHelp
        : cfg.i18n.passwordHelp;
      passwordDirty = false;
    }

    function post(action, extra) {
      var data = $.extend({ action: action, nonce: cfg.nonce, campaign_id: cfg.campaignId }, extra || {});
      return $.post(cfg.ajaxUrl, data);
    }

    password.addEventListener('input', function () {
      passwordDirty = true;
      copyPassword.disabled = !password.value;
    });

    save.addEventListener('click', function () {
      save.disabled = true;
      showNotice('', false);
      post('nowcastf_report_save', {
        enabled: enabled.checked ? 1 : 0,
        password: passwordDirty ? password.value : ''
      })
        .done(function (response) {
          if (!response || !response.success) {
            showNotice((response && response.data && response.data.message) || 'Unable to save report settings.', true);
            return;
          }
          applyState(response.data);
          showNotice(cfg.i18n.saved, false);
        })
        .fail(function (xhr) {
          var json = xhr.responseJSON;
          showNotice((json && json.data && json.data.message) || 'Unable to save report settings.', true);
        })
        .always(function () { save.disabled = false; });
    });

    regenerate.addEventListener('click', function () {
      regenerate.disabled = true;
      showNotice('', false);
      post('nowcastf_report_regenerate')
        .done(function (response) {
          if (response && response.success) {
            applyState(response.data);
            showNotice(cfg.i18n.saved, false);
          } else {
            showNotice((response && response.data && response.data.message) || 'Unable to regenerate link.', true);
          }
        })
        .fail(function () { showNotice('Unable to regenerate link.', true); })
        .always(function () { regenerate.disabled = false; });
    });

    copy.addEventListener('click', function () {
      if (copy.disabled || !linkInput.value) return;
      copyText(linkInput.value).then(function () {
        showNotice(cfg.i18n.copied, false);
      }).catch(function () {
        showNotice('Unable to copy link.', true);
      });
    });

    copyPassword.addEventListener('click', function () {
      if (copyPassword.disabled || !password.value) return;
      copyText(password.value).then(function () {
        showNotice(cfg.i18n.passwordCopied, false);
      }).catch(function () {
        showNotice('Unable to copy password.', true);
      });
    });

    sidebar.appendChild(card);
    applyState(state);
  }

  $(function () {
    enhanceMetrics();
    buildCard();
  });
})(jQuery);
