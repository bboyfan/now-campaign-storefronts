(function ($) {
  'use strict';

  var state = window.BboyfanNowCastfPresentation || {};
  var editorState = window.BboyfanNowCastfEditor || {};
  if (!state.campaignId) return;

  var media = Array.isArray(state.media) ? state.media.slice() : [];
  var design = Object.assign({
    page_bg: '',
    text_color: '',
    accent_color: '',
    surface_color: '',
    border_color: '',
    content_width: ''
  }, state.design || {});
  var sectionDesign = {};
  var observer = null;

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function ensureHiddenInputs() {
    var form = document.getElementById('nowcastf-editor-form');
    if (!form) return;
    [
      ['nowcastf-media-ids', 'campaign_media_ids'],
      ['nowcastf-design-json', 'campaign_design_json'],
      ['nowcastf-section-design-json', 'section_design_json']
    ].forEach(function (item) {
      if (document.getElementById(item[0])) return;
      var input = document.createElement('input');
      input.type = 'hidden';
      input.id = item[0];
      input.name = item[1];
      form.appendChild(input);
    });
  }

  function mediaMarkup() {
    if (!media.length) {
      return '<div class="nowcastf-gallery-empty"><span class="dashicons dashicons-format-gallery"></span><p>' + escapeHtml(state.i18n && state.i18n.emptyGallery ? state.i18n.emptyGallery : 'No campaign images have been added yet.') + '</p></div>';
    }
    return '<div class="nowcastf-gallery-grid" data-campaign-gallery-sortable>' + media.map(function (item) {
      return '<figure class="nowcastf-gallery-item" data-media-id="' + Number(item.id) + '">' +
        '<span class="nowcastf-gallery-drag dashicons dashicons-menu" aria-hidden="true"></span>' +
        '<img src="' + escapeHtml(item.thumb || item.url) + '" alt="' + escapeHtml(item.alt || '') + '">' +
        '<button type="button" class="button-link-delete" data-campaign-media-remove aria-label="' + escapeHtml(state.i18n && state.i18n.removeImage ? state.i18n.removeImage : 'Remove image') + '"><span class="dashicons dashicons-no-alt"></span></button>' +
      '</figure>';
    }).join('') + '</div>';
  }

  function renderGallery() {
    var root = document.querySelector('[data-campaign-gallery-root]');
    if (!root) return;
    root.querySelector('[data-campaign-gallery-content]').innerHTML = mediaMarkup();
    var sortable = $('[data-campaign-gallery-sortable]');
    if (sortable.length && $.fn.sortable) {
      sortable.sortable({
        items: '.woo-campaign-gallery-item',
        handle: '.woo-campaign-gallery-drag',
        update: function () {
          var order = sortable.find('[data-media-id]').map(function () { return Number($(this).attr('data-media-id')); }).get();
          media.sort(function (a, b) { return order.indexOf(Number(a.id)) - order.indexOf(Number(b.id)); });
        }
      });
    }
  }

  function openMediaFrame() {
    if (!window.wp || !wp.media) return;
    var frame = wp.media({
      title: state.i18n && state.i18n.imagesTitle ? state.i18n.imagesTitle : 'Campaign Images',
      button: { text: state.i18n && state.i18n.addImages ? state.i18n.addImages : 'Add images' },
      library: { type: 'image' },
      multiple: true
    });
    frame.on('select', function () {
      var selected = frame.state().get('selection').toJSON();
      var existing = new Set(media.map(function (item) { return Number(item.id); }));
      selected.forEach(function (attachment) {
        if (!attachment || !attachment.id || existing.has(Number(attachment.id))) return;
        var sizes = attachment.sizes || {};
        media.push({
          id: Number(attachment.id),
          thumb: sizes.thumbnail ? sizes.thumbnail.url : (sizes.medium ? sizes.medium.url : attachment.url),
          url: sizes.large ? sizes.large.url : attachment.url,
          alt: attachment.alt || ''
        });
        existing.add(Number(attachment.id));
      });
      renderGallery();
    });
    frame.open();
  }

  function colorControl(label, key, fallback) {
    var actual = design[key] || '';
    return '<div class="nowcastf-design-control" data-design-row="' + escapeHtml(key) + '">' +
      '<span>' + escapeHtml(label) + '<em class="nowcastf-design-status" data-campaign-design-status>' + escapeHtml(actual ? (state.i18n && state.i18n.overridden ? state.i18n.overridden : 'Set') : (state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set')) + '</em></span>' +
      '<div class="nowcastf-color-control' + (actual ? '' : ' is-inherit') + '">' +
        '<input type="color" aria-label="' + escapeHtml(label) + '" value="' + escapeHtml(actual || fallback) + '" data-campaign-design-color="' + escapeHtml(key) + '">' +
        '<button type="button" class="button-link" data-campaign-design-reset="' + escapeHtml(key) + '">' + escapeHtml(state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set') + '</button>' +
      '</div>' +
    '</div>';
  }

  function widthControl() {
    var actual = Number(design.content_width || 0);
    return '<div class="nowcastf-design-control"><span>Content width (px)<em class="nowcastf-design-status" data-campaign-design-status>' + escapeHtml(actual ? (state.i18n && state.i18n.overridden ? state.i18n.overridden : 'Set') : (state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set')) + '</em></span>' +
      '<div class="nowcastf-color-control' + (actual ? '' : ' is-inherit') + '">' +
        '<input type="number" aria-label="Content width (px)" min="800" max="1600" step="10" value="' + (actual || 1200) + '" data-campaign-design-width>' +
        '<button type="button" class="button-link" data-campaign-design-width-reset>' + escapeHtml(state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set') + '</button>' +
      '</div>' +
    '</div>';
  }

  function injectCampaignPanels() {
    var contentCard = document.querySelector('[data-campaign-content-card]');
    var designCard = document.querySelector('[data-campaign-design-card]');
    if (!contentCard || !designCard || contentCard.querySelector('[data-campaign-gallery-root]')) return;

    var description = contentCard.querySelector('textarea[name="campaign_description"]');
    var descriptionField = description ? description.closest('label') : null;

    var gallery = document.createElement('div');
    gallery.className = 'nowcastf-gallery-panel woo-campaign-editor-field';
    gallery.setAttribute('data-campaign-gallery-root', '');
    gallery.innerHTML = '<div class="nowcastf-presentation-heading"><div><span>Campaign media</span><h3>' + escapeHtml(state.i18n && state.i18n.imagesTitle ? state.i18n.imagesTitle : 'Campaign images') + '</h3><p>' + escapeHtml(state.i18n && state.i18n.imagesHelp ? state.i18n.imagesHelp : '') + '</p></div><button type="button" class="button" data-campaign-media-add><span class="dashicons dashicons-images-alt2"></span>' + escapeHtml(state.i18n && state.i18n.addImages ? state.i18n.addImages : 'Add images') + '</button></div><div data-campaign-gallery-content></div>';
    if (descriptionField) contentCard.insertBefore(gallery, descriptionField);
    else contentCard.appendChild(gallery);

    if (description) {
      description.id = 'nowcastf-rich-editor';
      var labelText = descriptionField && descriptionField.querySelector(':scope > span');
      if (labelText) labelText.textContent = state.i18n && state.i18n.introLabel ? state.i18n.introLabel : 'Campaign introduction';
      function initializeRichEditor() {
        if (!window.wp || !wp.editor || typeof wp.editor.initialize !== 'function' || !window.tinymce || typeof window.tinymce.init !== 'function') {
          return;
        }
        if (window.tinymce.get('nowcastf-rich-editor')) return;
        wp.editor.initialize('nowcastf-rich-editor', {
          tinymce: {
            wpautop: true,
            toolbar1: 'formatselect,bold,italic,forecolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,undo,redo',
            block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4'
          },
          quicktags: true,
          mediaButtons: false
        });
      }
      initializeRichEditor();
      window.addEventListener('load', initializeRichEditor, { once: true });
    }

    var designPanel = document.createElement('div');
    designPanel.className = 'nowcastf-design-panel woo-campaign-editor-field';
    designPanel.innerHTML = '<div class="nowcastf-design-grid">' +
        colorControl('Page background', 'page_bg', '#ffffff') +
        colorControl('Text color', 'text_color', '#222222') +
        colorControl('Accent color', 'accent_color', '#222222') +
        colorControl('Surface color', 'surface_color', '#ffffff') +
        colorControl('Border color', 'border_color', '#dddddd') +
        widthControl() +
      '</div>';
    designCard.querySelector('[data-campaign-design-content]').appendChild(designPanel);

    renderGallery();
  }

  function sectionIdForKey(clientKey) {
    var list = Array.isArray(editorState.sections) ? editorState.sections : [];
    var section = list.find(function (item) { return item.clientKey === clientKey; });
    return section ? Number(section.id || 0) : 0;
  }

  function ensureSectionDesignState(clientKey) {
    if (sectionDesign[clientKey]) return sectionDesign[clientKey];
    var id = sectionIdForKey(clientKey);
    var existing = id && state.sections ? state.sections[String(id)] : null;
    sectionDesign[clientKey] = Object.assign({ title_color: '', cta_bg_color: '', cta_text_color: '' }, existing || {});
    return sectionDesign[clientKey];
  }

  function sectionColorControl(label, key, clientKey, fallback) {
    var values = ensureSectionDesignState(clientKey);
    var actual = values[key] || '';
    return '<div class="nowcastf-section-design-control"><span>' + escapeHtml(label) + '</span><div class="nowcastf-color-control' + (actual ? '' : ' is-inherit') + '"><input type="color" aria-label="' + escapeHtml(label) + '" value="' + escapeHtml(actual || fallback) + '" data-section-design-color="' + escapeHtml(key) + '"><button type="button" class="button-link" data-section-design-reset="' + escapeHtml(key) + '">' + escapeHtml(state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set') + '</button></div></div>';
  }

  function injectSectionDesignControls() {
    document.querySelectorAll('[data-section-key]').forEach(function (block) {
      if (block.querySelector('[data-section-design-panel]')) return;
      var key = block.getAttribute('data-section-key');
      ensureSectionDesignState(key);
      var panel = document.createElement('div');
      panel.className = 'nowcastf-section-design-panel';
      panel.setAttribute('data-section-design-panel', '');
      panel.innerHTML = '<div class="nowcastf-subheading"><strong>Section Design Override</strong><span>' + escapeHtml(state.i18n && state.i18n.sectionDesignHelp ? state.i18n.sectionDesignHelp : 'Empty values inherit from the campaign or active theme.') + '</span></div><div class="nowcastf-section-design-grid">' +
        sectionColorControl('Title color', 'title_color', key, '#222222') +
        sectionColorControl('CTA background', 'cta_bg_color', key, '#222222') +
        sectionColorControl('CTA text color', 'cta_text_color', key, '#ffffff') +
      '</div>';
      var layout = block.querySelector('.woo-campaign-layout-picker');
      if (layout && layout.nextSibling) layout.parentNode.insertBefore(panel, layout.nextSibling);
      else if (layout) layout.parentNode.appendChild(panel);
    });
  }

  function serializePresentation() {
    if (window.tinymce) {
      var rich = window.tinymce.get('nowcastf-rich-editor');
      if (rich) rich.save();
    }
    var mediaInput = document.getElementById('nowcastf-media-ids');
    var designInput = document.getElementById('nowcastf-design-json');
    var sectionInput = document.getElementById('nowcastf-section-design-json');
    if (mediaInput) mediaInput.value = JSON.stringify(media.map(function (item) { return Number(item.id); }));
    if (designInput) designInput.value = JSON.stringify(design);
    if (sectionInput) {
      var keyed = {};
      document.querySelectorAll('[data-section-key]').forEach(function (block) {
        var clientKey = block.getAttribute('data-section-key');
        keyed[clientKey] = ensureSectionDesignState(clientKey);
      });
      sectionInput.value = JSON.stringify(keyed);
    }
  }

  ensureHiddenInputs();
  injectCampaignPanels();
  injectSectionDesignControls();

  var builder = document.getElementById('nowcastf-sections-builder');
  if (builder && window.MutationObserver) {
    observer = new MutationObserver(function () { injectSectionDesignControls(); });
    observer.observe(builder, { childList: true, subtree: true });
  }

  $(document).on('click', '[data-campaign-media-add]', openMediaFrame);
  $(document).on('click', '[data-campaign-media-remove]', function () {
    var id = Number($(this).closest('[data-media-id]').attr('data-media-id'));
    media = media.filter(function (item) { return Number(item.id) !== id; });
    renderGallery();
  });

  $(document).on('input', '[data-campaign-design-color]', function () {
    var key = $(this).attr('data-campaign-design-color');
    design[key] = this.value;
    var control = $(this).closest('.woo-campaign-color-control');
    control.removeClass('is-inherit');
    control.closest('[data-design-row]').find('[data-campaign-design-status]').text(state.i18n && state.i18n.overridden ? state.i18n.overridden : 'Set');
  });
  $(document).on('click', '[data-campaign-design-reset]', function () {
    var key = $(this).attr('data-campaign-design-reset');
    design[key] = '';
    var control = $(this).closest('.woo-campaign-color-control');
    control.addClass('is-inherit');
    control.closest('[data-design-row]').find('[data-campaign-design-status]').text(state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set');
  });
  $(document).on('input', '[data-campaign-design-width]', function () {
    design.content_width = Math.max(800, Math.min(1600, Number(this.value || 1200)));
    var control = $(this).closest('.woo-campaign-color-control');
    control.removeClass('is-inherit');
    control.closest('.woo-campaign-design-control').find('[data-campaign-design-status]').text(state.i18n && state.i18n.overridden ? state.i18n.overridden : 'Set');
  });
  $(document).on('click', '[data-campaign-design-width-reset]', function () {
    design.content_width = '';
    var control = $(this).closest('.woo-campaign-color-control');
    control.addClass('is-inherit');
    control.closest('.woo-campaign-design-control').find('[data-campaign-design-status]').text(state.i18n && state.i18n.inherit ? state.i18n.inherit : 'Not set');
  });

  $(document).on('input', '[data-section-design-color]', function () {
    var block = $(this).closest('[data-section-key]');
    var values = ensureSectionDesignState(block.attr('data-section-key'));
    values[$(this).attr('data-section-design-color')] = this.value;
    $(this).closest('.woo-campaign-color-control').removeClass('is-inherit');
  });
  $(document).on('click', '[data-section-design-reset]', function () {
    var block = $(this).closest('[data-section-key]');
    var values = ensureSectionDesignState(block.attr('data-section-key'));
    values[$(this).attr('data-section-design-reset')] = '';
    $(this).closest('.woo-campaign-color-control').addClass('is-inherit');
  });

  $('#woo-campaign-editor-form').on('submit', serializePresentation);
})(jQuery);
