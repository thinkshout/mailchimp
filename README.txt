This module provides integration with the Mailchimp email delivery service.
While tools for sending email from your own server, like SimpleNews, are great,
they lack the sophistication and ease of use of dedicated email providers like
Mailchimp. Other players in the field are Constant Contact and Campaign Monitor.

The core module provides basic configuration and API integration. Features and
site functionality are provided by a set of submodules that depend upon the core
"mailchimp" module. These are in the "modules" subdirectory: See their
respective README's for more details.

## Features
  * API integration
  * Support for an unlimited number of mailing lists
  * Have anonymous sign up forms to subscribe site visitors to any combination
    of Mailchimp lists
  * Mailchimp list subscription via entity fields, allowing subscription rules
    to be governed by entity controls, permissions, and UI
  * Allow users to subscribe during registration by adding a field to Users
  * Map Entity field values to your Mailchimp merge fields
  * Standalone subscribe and unsubscribe forms
  * Subscriptions can be maintained via cron or in real time
  * Subscription forms can be created as pages or as blocks, with one or more
    list subscriptions on a single form
  * Include merge fields & interest groups on anonymous subscription forms
  * Create & send Mailchimp Campaigns from within Drupal, using Drupal entities
    as content.
  * Display a history of Mailchimp email and subscription activity on a tab for
    any Entity with an email address.

## Installation Notes
  * You need to have a Mailchimp API Key.
  * You need to have at least one list created in Mailchimp to use the
    mailchimp_lists module.

## Configuration
  1. Direct your browser to admin/config/services/mailchimp to configure the
  module.

  2. You will need to put in your Mailchimp API key for your Mailchimp account.
  If you do not have a Mailchimp account, go to
  [http://www.mailchimp.com]([http://www.mailchimp.com) and sign up for a new
  account. Once you have set up your account and are logged in, visit:
  Account Settings -> Extras -> API Keys to generate a key.

  3. Copy your newly created API key and go to the
  [Mailchimp config](http://example.com/admin/config/services/mailchimp) page in
  your Drupal site and paste it into the Mailchimp API Key field.

  4. Batch limit - Maximum number of changes to process in a single cron run.
  Mailchimp suggest keeping this below 10000.

## Submodules
  * mailchimp_signup: Create anonymous signup forms for your Mailchimp Lists,
    and display them as blocks or as standalone pages. Provide multiple-list
    subscription from a single form, include merge variables as desired, and
    optionally include Interest Group selection.
  * mailchimp_lists: Subscribe any entity with an email address to Mailchimp
    lists by creating a mailchimp_list field, and allow anyone who can edit such
    an entity to subscribe, unsubscribe, and update member information. Also
    allows other entity fields to be synced to Mailchimp list Merge Fields. Add
    a Mailchimp Subscription field to your User bundle to allow Users to control
    their own subscriptions and subscribe during registration.
  * mailchimp_campaigns: Create and send campaigns directly from Drupal, or just
    create them and use the Mailchimp UI to send them. Embed content from your
    Drupal site by dropping in any Entity with a title and a View Mode
    configured into any area of your email template.
  * mailchimp_activity: Display a tab on any entity with an email address
    showing the email, subscribe, and unsubscribe history for that email address
    on your Mailchimp account.
    IMPORTANT: This module has not yet been ported to Drupal 8, but will be in
    subsequent releases.

## Related Modules
### Mandrill
  * Mandrill is Mailchimp's transactional email service. The module provides the
    ability to send all site emails through Mandrill with reporting available
    from within Drupal. Please refer to the project page for more details.
  * http://drupal.org/project/mandrill
### MCC, an alternative campaign creation tool.
  * http://drupal.org/project/mcc
