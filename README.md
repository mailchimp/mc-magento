# MailChimp For Magento 1
[![Build Status](https://travis-ci.org/mailchimp/mc-magento.svg?branch=develop)](https://travis-ci.org/mailchimp/mc-magento)

## MailChimp Integration

Integration to sync all the Magento data (Newsletter subscriber, Customers, Orders, Products) with MailChimp. It allows to use all the MailChimp potential for email Marketing such as sending Campaigns, Automations and more.

## Mandrill Integration

The integration includes a Mandrill SMTP module that overrides the one integrated from Magento, you will need to enable Mandrill with its API key from your Mandrill account (mandrillapp.com) for the transactional emails to work.
If you want to use the Mailchimp integration without Mandrill for SMTP, contact our support so we can tell you how to set that up.

## Features

* Two way sync between a MailChimp list and Magento’s newsletter
* Responsive Email Catcher Popup when accessing the site
* Compatibility with all the <a href="http://mailchimp.com/features/all/" target="_blank">MailChimp Features</a>

## Prerequisities

Magento Community Edition (1.7 or above) or Magento Enterprise (1.11 or above)

<a href="http://www.mailchimp.com/signup?pid=ebizmarts&source=website" target="_blank">MailChimp Account</a>

## Installation

To get a copy of the project up and running on your local machine for development and testing purposes, just clone this repository on your Magento’s root directory and flush the Magento’s cache.

Alternatively, use modman to install this module.

``modman clone https://github.com/mailchimp/mc-magento.git -b 'master'``

## Module Configuriation

To enable MailChimp For Magento:

1. Go to System -> Configuration -> MAILCHIMP -> MailChimp Configuration -> Select scope on your Magento’s back end.<br />
2. Click the <b>Get API credentials</b> and place your MailChimp credentials, then an API Key will be shown.<br />
3. Paste the API Key on MailChimp For Magento’s configuration and click <b>Save Config</b><br />
4. When the page is loaded again select the desired list to sync with the Magento’s newsletter list. At this point your Magento subscribers will start being sent to the configured MailChimp list.<br />
5. If you have a paid MailChimp account and want to use MailChimp Automations go to "<b>Default Config</b>" scope and to the Ecommerce section and set it to Enabled. Now all your store information (Products, orders, customers and carts) will start being sent to MailChimp's associated list at your "<b>Default Config</b>" scope.

More guides and tutorials about the Mailchimp integration with Magento can be found on the [tutorial page of Mailchimp](https://mailchimp.com/help/connect-or-disconnect-mailchimp-for-magento/).

## Report issues

For reporting issues, follow this [guidelines](https://github.com/mailchimp/mc-magento/wiki/Issue-reporting-guidelines) or your issue will be rejected.

<h3>Labels applied by the team</h3>

| Label        | Description           |
| ------------- |-------------|
| ![bug](https://s3.amazonaws.com/ebizmartsgithubimages/bug.png) | Bug report contains sufficient information to reproduce. Will be solved for associated Milestone.|
| ![enhancement](https://s3.amazonaws.com/ebizmartsgithubimages/enhancement.png) | Improvement accepted. Will be added for associated Milestone.|
| ![done](https://s3.amazonaws.com/ebizmartsgithubimages/done.png) | Issue has been solved and will be applied in the associated Milestone. |
| ![duplicate](https://s3.amazonaws.com/ebizmartsgithubimages/duplicate.png) | Issue has been already reported and will be closed with no further action. |
| ![wrong issue format](https://s3.amazonaws.com/ebizmartsgithubimages/wrongissueformat.png) | Issue has not been created according to requirements at the [Issue reporting guidelines](https://github.com/mailchimp/mc-magento/wiki/Issue-reporting-guidelines). Will be closed until requirements are met. |
| ![feature request](https://s3.amazonaws.com/ebizmartsgithubimages/featurerequest.png) | Feature request to be considered by the team. After approval will be labeled as enhancement. |
| ![could not replicate](https://s3.amazonaws.com/ebizmartsgithubimages/couldnotreplicate.png) | The team was not able to replicate issue. It will be closed until missing information is given. |
| ![contact support](https://s3.amazonaws.com/ebizmartsgithubimages/contactsupport.png) | Contact our support team at mailchimp@ebizmarts-desk.zendesk.com. Issue will be closed with no further action. |
| ![low priority](https://s3.amazonaws.com/ebizmartsgithubimages/lowpriority.png) | Issue is considered as low priority by the team. |
| ![priority](https://s3.amazonaws.com/ebizmartsgithubimages/priority.png) | Issue is considered as high priority by the team. |
| ![conflict](https://s3.amazonaws.com/ebizmartsgithubimages/conflict.png) | Issue reports a conflict with other third party extension. |
| ![need feedback](https://s3.amazonaws.com/ebizmartsgithubimages/needfeedback.png) | Feedback is required to continue working on the issue. If there is no answer after a week it will be closed. |
| ![blocked](https://s3.amazonaws.com/ebizmartsgithubimages/blocked.png) | Issue can not be solved due to external causes. |
| ![read documentation](https://s3.amazonaws.com/ebizmartsgithubimages/readdocumentation.png) | Issue will be closed. Available documentation: [MailChimp For Magento doc](https://kb.mailchimp.com/integrations/e-commerce/connect-or-disconnect-mailchimp-for-magento)|

## Pull requests

Before creating a pull request please make sure to follow this [guidelines](https://github.com/mailchimp/mc-magento/wiki/Pull-Request-guideliness) or it will be rejected.

## Support

Need support? [Click here](http://ebizmarts.com/forums/view/6)

## License

[Open Software License (OSL 3.0)](http://opensource.org/licenses/osl-3.0.php)
