$Id:
This module provides integration with the MailChimp (http://mailchimp.com) email
delivery service. I needed it for a community focused site, and we found the 
"internal" tools like simple news a bit lacking, especially in terms of 
composition, delivery, and reporting.

This module is still in early development, but here are the current features:
   1. Support for an unlimited number of mailing lists
   2. Having an anonymous sign up form to enroll users in a general newsletter.
   3. list access by role
   4. editing of user list subscriptions on the user's edit page
   5. list subscribe on register page
   6. customizable merge vars with token, profile and bio.module integreation
   7. opt-in, opt-out and required lists
   8. standalone subscribe and unsubscribe forms 
   
Big thanks to Ronan Dowling (http://drupal.org/user/72815) for his help in development.

Installing Mailchimp:
  Download the Mailchimp release for your version.
  Untar it in the modules directory.
  Activate the module through drupals administrative interface.