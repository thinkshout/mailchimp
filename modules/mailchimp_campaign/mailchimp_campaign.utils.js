(function ($) {

  /**
   * Utility methods for MailChimp campaign management.
   * Some text field manipulation code is adapted from
   * the Tokens module.
   */
  Drupal.behaviors.mailchimp_campaign_utils = {
    attach: function(context, settings) {
      // Hide entity import tag field by default.
      $('#entity-import-tag-field').hide();

      // Keep track of which textfield was last selected/focused.
      $('textarea', context).focus(function() {
        Drupal.settings.mailchimpCampaignFocusedField = this;
        console.log('Got text field focus: ' + $(this).attr('id'));
      });

      $('#add-entity-token', context).unbind('click').bind('click', function() {
        // Get the last selected text field.
        var target_element = Drupal.settings.mailchimpCampaignFocusedField;

        // Get the selected entity ID.
        var entity_id = '';
        var entity_value = $('.entity-import-entity-id').val();
        if ((entity_value) && (entity_value.length > 0)) {
          var entity_parts = entity_value.split(' ');
          var entity_id_string = entity_parts[entity_parts.length - 1];

          entity_id = entity_id_string.replace('[', '').replace(']', '');
        }

        if (entity_id.length == 0) {
          alert(Drupal.t('Select an entity to import before adding the token.'));
          return;
        }

        // Generate token based on user input.
        var entity_type = $('.entity-import-entity-type').val();
        var view_mode = $('.entity-import-entity-view-mode').val();

        var token = '[mailchimp_campaign'
          + '|entity_type=' + entity_type
          + '|entity_id=' + entity_id
          + '|view_mode=' + view_mode
          + ']';

        // Insert token into last selected text field.
        if (target_element) {
          console.log('Inserting token: ' + token);

          Drupal.behaviors.mailchimp_campaign_utils.addTokenToElement(target_element, token);
        }
        else {
          // Insert token into token field, where it can be manually copied
          // by the user. This is a fallback for cases where WYSIWYG text
          // fields prevent automatic insertion of the token.
          $('#entity-import-tag-field input').val(token);
          $('#entity-import-tag-field').show();
        }

        // Unset last focused field.
        Drupal.settings.mailchimpCampaignFocusedField = null;
      });

      $('.add-merge-var', context).unbind('click').bind('click', function() {
        // Get the last selected text field.
        var target_element = Drupal.settings.mailchimpCampaignFocusedField;

        // Get the merge var.
        var element_id = $(this).attr('id');
        var merge_var = element_id.replace('merge-var-', '');
        var token = '*|' + merge_var + '|*';

        // Insert token into last selected text field.
        if (target_element) {
          console.log('Inserting token: ' + token);

          $('#entity-import-tag-field').hide();

          Drupal.behaviors.mailchimp_campaign_utils.addTokenToElement(target_element, token);
        }

        // Unset last focused field.
        Drupal.settings.mailchimpCampaignFocusedField = null;
      });
    },

    addTokenToElement: function(target_element, token) {
      // IE support.
      if (document.selection) {
        target_element.focus();
        sel = document.selection.createRange();
        sel.text = token;
      }

      // MOZILLA/NETSCAPE support.
      else if (target_element.selectionStart || target_element.selectionStart == '0') {
        var startPos = target_element.selectionStart;
        var endPos = target_element.selectionEnd;
        target_element.value = target_element.value.substring(0, startPos)
          + token
          + target_element.value.substring(endPos, target_element.value.length);
      } else {
        target_element.value += token;
      }
    }
  }

})(jQuery);
