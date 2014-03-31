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

      $('#add-entity-token', context).one('click', function() {
        var element_id = $(this).attr('id');

        // Determine form section using this element's ID.
        var element_id_parts = element_id.split('-');
        var section = element_id_parts[element_id_parts.length - 1];

        // Get the last selected text field.
        var target_element = Drupal.settings.mailchimpCampaignFocusedField;

        // Get the selected entity ID.
        var entity_id = '';
        var entity_value = $('#edit-content-entity-' + section + '-entity').val();
        if (entity_value.length > 0) {
          var entity_parts = entity_value.split(' ');
          var entity_id_string = entity_parts[entity_parts.length - 1];

          entity_id = entity_id_string.replace('[', '').replace(']', '');
        }

        // Generate token based on user input.
        var entity_type = $('#edit-content-entity-' + section + '-entity-type').val();
        var view_mode = $('#edit-content-entity-' + section + '-entity-view-mode').val();

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
