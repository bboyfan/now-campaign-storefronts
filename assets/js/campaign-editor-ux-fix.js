(function ($) {
  'use strict';

  var EDITOR_ID = 'woo-campaign-rich-editor';
  var initialized = false;

  function movePublishActions() {
    var publishCard = document.querySelector('.woo-campaign-editor-sidebar .sticky-card');
    var actions = document.querySelector('.woo-campaign-editor-topbar .woo-campaign-editor-actions');
    if (!publishCard || !actions) return;

    actions.classList.add('woo-campaign-publish-actions');
    publishCard.appendChild(actions);
  }

  function normalizeEditorField(textarea) {
    var field = textarea.closest('label.woo-campaign-editor-field');
    if (!field) return textarea.closest('.woo-campaign-editor-field');

    var replacement = document.createElement('div');
    replacement.className = field.className + ' woo-campaign-native-editor-field';
    while (field.firstChild) replacement.appendChild(field.firstChild);
    field.replaceWith(replacement);
    return replacement;
  }

  function restoreNativeEditor() {
    if (initialized) return true;
    if (!window.wp || !wp.editor || typeof wp.editor.initialize !== 'function') return false;
    if (!window.tinymce || typeof window.tinymce.init !== 'function') return false;

    var textarea = document.querySelector('textarea[name="campaign_description"]');
    if (!textarea) return false;

    textarea.id = EDITOR_ID;
    var content = textarea.value || '';
    var existing = window.tinymce.get(EDITOR_ID);
    if (existing) content = existing.getContent();

    if (typeof wp.editor.remove === 'function') {
      try {
        wp.editor.remove(EDITOR_ID);
      } catch (error) {
        // Keep going; initialize() below is guarded against duplicate TinyMCE instances.
      }
    }

    textarea = document.getElementById(EDITOR_ID) || document.querySelector('textarea[name="campaign_description"]');
    if (!textarea) return false;
    textarea.value = content;

    var field = normalizeEditorField(textarea);
    if (field) {
      var label = field.querySelector(':scope > span');
      if (label) label.textContent = (window.WooCampaignPresentation && WooCampaignPresentation.i18n && WooCampaignPresentation.i18n.introLabel) || 'Campaign introduction';
    }

    try {
      wp.editor.initialize(EDITOR_ID, {
        tinymce: {
          wpautop: true,
          toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
          toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
          block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6'
        },
        quicktags: true,
        mediaButtons: true
      });
      initialized = true;
      return true;
    } catch (error) {
      return false;
    }
  }

  function retryEditor(attempt) {
    if (restoreNativeEditor()) return;
    if (attempt >= 80) return;
    window.setTimeout(function () { retryEditor(attempt + 1); }, 100);
  }

  function syncEditorBeforeSubmit() {
    if (!window.tinymce) return;
    var editor = window.tinymce.get(EDITOR_ID);
    if (editor) editor.save();
  }

  $(function () {
    movePublishActions();
    retryEditor(0);
    $('#woo-campaign-editor-form').on('submit.wooCampaignNativeEditor', syncEditorBeforeSubmit);
  });
})(jQuery);
