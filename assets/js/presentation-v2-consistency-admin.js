(function ($) {
  'use strict';

  var state = window.WooCampaignPresentation || {};
  var editorState = window.WooCampaignEditor || {};
  if (!state.campaignId) return;

  var copyColors = {};

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function setLabel() {
    return state.i18n && state.i18n.overridden ? state.i18n.overridden : 'Set';
  }

  function unsetLabel() {
    return state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set';
  }

  function sectionIdForKey(clientKey) {
    var list = Array.isArray(editorState.sections) ? editorState.sections : [];
    var section = list.find(function (item) { return item.clientKey === clientKey; });
    return section ? Number(section.id || 0) : 0;
  }

  function initialCopyColor(clientKey) {
    if (Object.prototype.hasOwnProperty.call(copyColors, clientKey)) return copyColors[clientKey];
    var id = sectionIdForKey(clientKey);
    var existing = id && state.sections ? state.sections[String(id)] : null;
    copyColors[clientKey] = existing && existing.copy_color ? existing.copy_color : '';
    return copyColors[clientKey];
  }

  function statusMarkup(isSet) {
    return '<em class="nowcastf-design-status" data-section-design-status>' + escapeHtml(isSet ? setLabel() : unsetLabel()) + '</em>';
  }

  function ensurePageTitleControl() {
    var grid = document.querySelector('.woo-campaign-design-grid');
    if (!grid || grid.querySelector('[data-campaign-show-title]')) return;
    var checked = !state.design || state.design.show_title !== false;
    var control = document.createElement('div');
    control.className = 'nowcastf-design-control woo-campaign-design-toggle';
    control.innerHTML = '<span>' + escapeHtml(state.i18n && state.i18n.showTitle ? state.i18n.showTitle : 'Show page title') + '</span>' +
      '<label><input type="checkbox" data-campaign-show-title' + (checked ? ' checked' : '') + '><span data-campaign-show-title-status>' + escapeHtml(checked ? setLabel() : unsetLabel()) + '</span></label>';
    grid.insertBefore(control, grid.firstChild);
  }

  function ensureExistingStatuses(block) {
    block.querySelectorAll('.woo-campaign-section-design-control').forEach(function (control) {
      var label = control.querySelector(':scope > span');
      var color = control.querySelector('.woo-campaign-color-control');
      if (!label || !color || label.querySelector('[data-section-design-status]')) return;
      label.insertAdjacentHTML('beforeend', statusMarkup(!color.classList.contains('is-inherit')));
    });
  }

  function ensureCopyControl(block) {
    var grid = block.querySelector('.woo-campaign-section-design-grid');
    if (!grid || grid.querySelector('[data-section-copy-color]')) return;
    var clientKey = block.getAttribute('data-section-key');
    var actual = initialCopyColor(clientKey);
    var control = document.createElement('div');
    control.className = 'nowcastf-section-design-control';
    control.setAttribute('data-section-copy-control', '');
    control.innerHTML = '<span>' + escapeHtml(state.i18n && state.i18n.copyColor ? state.i18n.copyColor : 'Campaign product copy color') + statusMarkup(!!actual) + '</span>' +
      '<div class="nowcastf-color-control' + (actual ? '' : ' is-inherit') + '">' +
        '<input type="color" aria-label="Campaign product copy color" value="' + escapeHtml(actual || '#555555') + '" data-section-copy-color>' +
        '<button type="button" class="button-link" data-section-copy-color-reset>' + escapeHtml(unsetLabel()) + '</button>' +
      '</div>';
    grid.appendChild(control);
  }

  function ensureSectionControls() {
    document.querySelectorAll('[data-section-key]').forEach(function (block) {
      ensureExistingStatuses(block);
      ensureCopyControl(block);
    });
  }

  function refreshStatus(control) {
    if (!control) return;
    var status = control.querySelector('[data-section-design-status]');
    var color = control.querySelector('.woo-campaign-color-control');
    if (status && color) status.textContent = color.classList.contains('is-inherit') ? unsetLabel() : setLabel();
  }

  function serializeExtras() {
    var show = document.querySelector('[data-campaign-show-title]');
    var designInput = document.getElementById('nowcastf-design-json');
    if (show && designInput) {
      var design = {};
      try { design = JSON.parse(designInput.value || '{}'); } catch (error) { design = {}; }
      design.show_title = !!show.checked;
      designInput.value = JSON.stringify(design);
    }

    var sectionsInput = document.getElementById('nowcastf-sections-json');
    if (!sectionsInput) return;
    var sections = [];
    try { sections = JSON.parse(sectionsInput.value || '[]'); } catch (error) { sections = []; }
    if (!Array.isArray(sections)) return;
    sections.forEach(function (section) {
      var key = section && section.client_key ? String(section.client_key) : '';
      if (key) section.copy_color = initialCopyColor(key);
    });
    sectionsInput.value = JSON.stringify(sections);
  }

  ensurePageTitleControl();
  ensureSectionControls();

  var builder = document.getElementById('nowcastf-sections-builder');
  if (builder && window.MutationObserver) {
    new MutationObserver(ensureSectionControls).observe(builder, { childList: true, subtree: true });
  }

  $(document).on('change', '[data-campaign-show-title]', function () {
    $('[data-campaign-show-title-status]').text(this.checked ? setLabel() : unsetLabel());
  });

  $(document).on('input', '[data-section-design-color]', function () {
    refreshStatus($(this).closest('.woo-campaign-section-design-control')[0]);
  });

  $(document).on('click', '[data-section-design-reset]', function () {
    var control = $(this).closest('.woo-campaign-section-design-control')[0];
    window.setTimeout(function () { refreshStatus(control); }, 0);
  });

  $(document).on('input', '[data-section-copy-color]', function () {
    var block = $(this).closest('[data-section-key]');
    var key = block.attr('data-section-key');
    copyColors[key] = this.value;
    var color = $(this).closest('.woo-campaign-color-control');
    color.removeClass('is-inherit');
    refreshStatus(color.closest('.woo-campaign-section-design-control')[0]);
  });

  $(document).on('click', '[data-section-copy-color-reset]', function () {
    var block = $(this).closest('[data-section-key]');
    var key = block.attr('data-section-key');
    copyColors[key] = '';
    var color = $(this).closest('.woo-campaign-color-control');
    color.addClass('is-inherit');
    refreshStatus(color.closest('.woo-campaign-section-design-control')[0]);
  });

  $('#woo-campaign-editor-form').on('submit', serializeExtras);
})(jQuery);
