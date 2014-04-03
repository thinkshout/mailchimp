Synchronize Drupal entities with MailChimp lists and allow anyone with access to
edit entities (such as Users editing their own data) to subscribe, unsubscribe,
and update membership information. This module requires the
[Entity module](http://www.drupal.org/project/entity).

## Installation

1. Enable the MailChimp Lists module, the Field UI module, and the Entity Module

2. To use MailChimp Lists module, you will need to install and enable the Entity
API module [http://drupal.org/project/entity]([http://drupal.org/project/entity)

3. If you haven't done so already, add a list in your MailChimp account. Follow 
these directions provided by MailChimp on how to 
[add or import a list](http://kb.mailchimp.com/article/how-do-i-create-and-import-my-list).

## Usage
* Subscription Field
Create an entity type with an email address field, or pick an entity that has an
email address property (like Users). Add a field to the entity type of the type
"Mailchimp Subscription" and use the field UI to configure your Subcription
field.

* Merge Fields
You will see Merge Field options based on the configuration of your list through
MailChimp. You can match these fields up to fields on your entity to push your
entity field values back to Mailchimp during subscriptions. 

## Field-level Settings

* Require subscribers to Double Opt-in
New subscribers will be sent a link with an email from MailChimp that they must 
follow to confirm their subscription, rather than being immediately subscribed
and send a confirmation message from MailChimp. 

## Webhooks

Direct your browser to: admin/config/services/mailchimp 
You will now see a "Lists" tab. (admin/config/services/mailchimp/lists)
This should show you your lists, and allow you to control Webhook settings for
each list.

What does this mean?
When a user unsubscribes from a list or updates their profile through MailChimp
rather than Drupal, MailChimp will trigger an event to update the user's cached
MailChimp member information. This will not update any of their merge field
data, or any other Entity data: it just changes the cached information. This
cached data means Drupal doesn't have to contact MailChimp every time it wants
to gather subscription data.

In other words, this should be enabled if possible. Otherwise, you may be using
innaccurate information in Drupal. It is also important to note that the webhook
doesn't just clear cached data, but actually updates the cached data.

*Note: You cannot test webhooks if developing locally, as the MailChimp system
can't reach your local computer unless you enable a tunneling service like
ngrok.*
