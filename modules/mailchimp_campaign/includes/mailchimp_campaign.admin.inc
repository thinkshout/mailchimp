<?php
/**
 * @file
 * Administration pages for mailchimp_campaign module.
 */

/**
 * Preview callback for mailchimp_campaign_campaign_form().
 */

// TODO: Move into MailchimpCampaignForm.

function mailchimp_campaign_campaign_preview($form, &$form_state) {
  $text = '';
  $template_content = _mailchimp_campaign_parse_template_content($form_state['values']['content']);
  $content = mailchimp_campaign_render_template($template_content);
  foreach ($content as $key => $section) {
    $text .= "<h3>$key</h3>" . $section;
  }

  $form_state['mailchimp_campaign_campaign_preview'] = $text;
  $form_state['rebuild'] = TRUE;
}

/**
 * Parses template content to remove wrapper elements from tree.
 *
 * @param array $content
 *   The template content array.
 *
 * @return array
 *   The template content array minus wrapper elements.
 */

// TODO: Already moved into MailchimpCampaignForm but still used for preview.
// Remove once mailchimp_campaign_campaign_preview is in MailchimpCampaignForm.

function _mailchimp_campaign_parse_template_content($content) {
  $template_content = array();
  $content_keys = array_keys($content);
  foreach ($content_keys as $content_key) {
    if (strpos($content_key, '_wrapper') !== FALSE) {
      // If this element is a wrapper, add the element contained
      // within the wrapper to the template content.
      $new_content_key = str_replace('_wrapper', '', $content_key);
      $template_content[$new_content_key] = $content[$content_key][$new_content_key];
    }
    else {
      // If this element is not a wrapper, add it to the template content.
      $template_content[$content_key] = $content[$content_key];
    }
  }
  return $template_content;
}
