(function ($) {

  Drupal.behaviors.mailchimp_campaign_utils = {
    attach: function(context, settings) {
      $('.add-entity-token', context).one('click', function() {
        var element_id = $(this).attr('id');

        console.log(element_id);
        var element_id_parts = element_id.split('-');
        var section = element_id_parts[element_id_parts.length - 1];

        var target_element_id = 'edit-content-html-' + section + '-value';

        var entity_id = '';
        var entity_value = $('#edit-content-entity-header-entity').val();
        if (entity_value.length > 0) {
          var entity_parts = entity_value.split(' ');
          var entity_id_string = entity_parts[entity_parts.length - 1];

          entity_id = entity_id_string.replace('[', '').replace(']', '');
        }

        var entity_type = $('#edit-content-entity-' + section + '-entity-type').val();
        var view_mode = $('#edit-content-entity-' + section + '-entity-view-mode').val();

        var token = '[mailchimp_campaign'
          + '|entity_type=' + entity_type
          + '|entity_id=' + entity_id
          + '|view_mode=' + view_mode
          + ']';

        console.log(token);

        $('#' + target_element_id).val(token);
      });
    }
  }

})(jQuery);
