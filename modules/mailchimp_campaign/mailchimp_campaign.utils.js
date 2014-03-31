(function ($) {

  /**
   * Utility methods for MailChimp campaign management.
   * Some text field manipulation code is adapted from
   * the Tokens module.
   */
  Drupal.behaviors.mailchimp_campaign_utils = {
    attach: function(context, settings) {
      // Keep track of which textfield was last selected/focused.
      $('textarea', context).focus(function() {
        Drupal.settings.mailchimpCampaignFocusedField = this;
        console.log('Got text field focus: ' + $(this).attr('id'));
      });

      $('#add-entity-token', context).unbind('click').bind('click', function() {
        // Get the last selected text field.
        var target_element = Drupal.settings.mailchimpCampaignFocusedField;

        if (typeof target_element == 'undefined') {
          alert(Drupal.t('First click a template section field to insert the token into.'));
          return;
        }

        // Get the selected entity ID.
        var entity_id = '';
        var entity_value = $('#edit-content-entity-import-entity').val();
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
        var entity_type = $('#edit-content-entity-import-entity-type').val();
        var view_mode = $('#edit-content-entity-import-entity-view-mode').val();

        var token = '[mailchimp_campaign'
          + '|entity_type=' + entity_type
          + '|entity_id=' + entity_id
          + '|view_mode=' + view_mode
          + ']';

        // Insert token into last selected text field.
        if (target_element) {
          console.log('Inserting token: ' + token);

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
        else {
          console.log('Unable to insert token; no text field selected.');
        }
      });
    }
  }

})(jQuery);
