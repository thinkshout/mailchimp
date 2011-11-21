This submodule allows for site emails to be sent through MailChimp STS (Simple 
Transactional Service). MailChimp STS is only available for monthly and 
pay-as-you-go accounts. MailChimp STS uses Amazon SES (Simple Email Service), 
where you will need an account.

You can follow instructions on how to do that and read more in the 
[MailChimp Documentation](http://kb.mailchimp.com/article/how-does-mailchimp-integrate-with-amazon-ses)

After you have connected Amazon SES to your MailChimp account, a MailChimp STS 
tab will appear in the MailChimp configuration of your Drupal site.
http://example.com/admin/config/services/mailchimp/sts

## Settings

### MailChimp STS Mail interface status 
* On: Setting to "On" routes all site emails through the STS API. 
* Test: Test mode implements an alternate mail interface, 
TestingMailChimpSTSMailSystem, that does not send any emails, it just displays 
a message and logs the event. You can view the logs in the MailChimp STS Reports 
located at http://example.com/admin/reports/mailchimp_sts
*Off: Setting MailChimp STS off routes all email through site's server.

### Email options
* Email from address - Select the email address you want your emails to be sent 
from.
* Verify New Email Address - To add a new Email Address to your Amazon SES 
account, add the email address here. Amazon will send the email address a 
confirmation message in which the user will need to click a link to confirm that 
you are authorized to use this email address.
* Input format - This selection allows you to select the optional input format 
to apply to the message body before sending to the STS API.

## Reports

MailChimp STS provides a set of integrated reports within Drupal at 
http://example.comadmin/reports/mailchimp_sts.

* STS Quota reports on the number of emails sent in the last 24 hours and the 
max number of emails your accounts supports per 24 hr period and per second.

* STS Send Statistics is the heart of the transactional email reporting system.
When Drupal sends an email, it's assocaited with a unique mail key. This key is
passed to MailChimp as the email tag and the reports are broken down by tag.
Example tags/keys include password resets, user registrations, comment 
notifications, etc. For each tag per hour, the report returns emails sent, 
bounces, rejections, complaints, opens, and clicks.