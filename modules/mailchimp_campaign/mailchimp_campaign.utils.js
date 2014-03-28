(function ($) {

  Drupal.mailchimp_campaign_add_entity_token = function(target_element_id) {
    console.log('Adding entity token to element: ' + target_element_id);

    var entity_id = '';
    var entity_value = $('#edit-content-entity-header-entity').val();
    if (entity_value.length > 0) {
      var entity_parts = entity_value.split(' ');
      var entity_id_string = entity_parts[entity_parts.length - 1];

      entity_id = entity_id_string.replace('[', '').replace(']', '');
    }

    var entity_type = $('#edit-content-entity-header-entity-type').val();
    var view_mode = $('#edit-content-entity-header-entity-view-mode').val();

    var token = '[mailchimp_campaign'
      + '|entity_type=' + entity_type
      + '|entity_id=' + entity_id
      + '|view_mode=' + view_mode
      + ']';

    console.log(token);

    $('#' + target_element_id).append(token);
  };

})(jQuery);
