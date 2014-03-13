Create pages and blocks for subscribing to MailChimp lists anywhere on your
Drupal site. This module requires the [Entity module](http://www.drupal.org/project/entity).

## Installation

1. Enable the MailChimp Signup module and the Entity Module

2. If you haven't done so already, add a list in your MailChimp account. Follow 
these directions provided by MailChimp on how to 
[add or import a list](http://kb.mailchimp.com/article/how-do-i-create-and-import-my-list).

## Usage
* Subscription Field
Create an entity type with an email address field. Add a field to the entity
type of the type "Mailchimp Subscription" and use the field UI to configure
your Subcription field.

* Merge Fields
You will see Merge Field options based on the configuration of your list through
MailChimp. You can match these fields up to fields on your entity to push your
entity field values back to Mailchimp during subscriptions. 

## Field-level Settings

* Require subscribers to Double Opt-in
New subscribers will be sent a link with an email from MailChimp that they must 
follow to confirm their subscription. 

## System-level Settings

Direct your browser to: http://example.com/admin/config/services/mailchimp 
You will now see a "Lists" tab. (http://example.com/admin/config/services/mailchimp/lists)
This should show you your lists, and allow you to control Webhook settings for
each list. What does this mean?

When a user unsubscribes from a list or updates their profile outside of Drupal, 
MailChimp will trigger an event to update the user's cached MailChimp member 
information. This will not update any of their Drupal user information. Web 
hooks are the only way to maintain a two-way sync with your lists. Any 
information updated outside of the Drupal environment, e.g., email footer, 
another website, MailChimp site directly, etc. will trigger an update of the 
cached member information within Drupal. This cached data means Drupal doesn't 
have to contact MailChimp every time it wants to determine a user's status, get 
their other info, etc. Generally this should be enabled if possible. Otherwise, 
lists could get out of sync. It is also important to note that the web hook
doesn't just clear cached data, but actually updates the cached data.
*Note: You cannot test webhooks if developing locally, as the MailChimp system
can't reach your local computer unless you enable a tunneling service like
ngrok.*
