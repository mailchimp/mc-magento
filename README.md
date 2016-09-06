# MailChimp For Magento 1

Integration to sync all the Magento data (Newsletter subscriber, Customers, Orders, Products) with MailChimp. It allows to use all the MailChimp potential for email Marketing such as sending Campaigns, Automations and more.

## Features

* Two way sync between a MailChimp list and Magento’s newsletter
* Responsive Email Catcher Popup when accessing the site
* Compatibility with all the <a href="http://mailchimp.com/features/all/" target="_blank">MailChimp Features</a>

## Prerequisities

Magento Community Edition (1.7 or above) or Magento Enterprise (1.11 or above)

<a href="http://www.mailchimp.com/signup?pid=ebizmarts&source=website" target="_blank">MailChimp Account</a>

## Installation

To get a copy of the project up and running on your local machine for development and testing purposes, just clone this repository on your Magento’s root directory and flush the Magento’s cache.

## Module Configuriation

To enable MailChimp For Magento:

1. Go to System -> Configuration -> MAILCHIMP -> MailChimp Configuration -> Select scope on your Magento’s back end.<br />
2. Click the <b>Get API credentials</b> and place your MailChimp credentials, then an API Key will be shown.<br />
3. Paste the API Key on MailChimp For Magento’s configuration and click <b>Save Config</b><br />
4. When the page is loaded again select the desired list to sync with the Magento’s newsletter list. At this point your Magento subscribers will start being sent to the configured MailChimp list.<br />
5. If you have a paid MailChimp account and want to use MailChimp Automations go to "<b>Default Config</b>" scope and to the Ecommerce section and set it to Enabled. Now all your store information (Products, orders, customers and carts) will start being sent to MailChimp's associated list at your "<b>Default Config</b>" scope.

## Report issues

For reporting issues, follow [this guidelines](https://github.com/mailchimp/mc-magento/wiki/Issue-reporting-guidelines) or your issue will be rejected

## Support

Need support? [Click here](http://ebizmarts.com/forums/view/6)

## License

[Open Software License (OSL 3.0)](http://opensource.org/licenses/osl-3.0.php)
