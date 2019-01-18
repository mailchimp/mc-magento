# Change Log

## [1.1.14](https://github.com/mailchimp/mc-magento/tree/1.1.14) (2019-01-16)

[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.13...1.1.14)

**Implemented enhancements:**

- Replace old MailChimp logo with the new one. [\#839](https://github.com/mailchimp/mc-magento/issues/839)
- create a reference to the Mailchimp tutorials [\#847](https://github.com/mailchimp/mc-magento/pull/847) ([hans2103](https://github.com/hans2103))

**Fixed bugs:**

- Orders belonging to deleted stores do not show correct syncing status under "synced to MailChimp" column [\#840](https://github.com/mailchimp/mc-magento/issues/840)

## [1.1.13](https://github.com/mailchimp/mc-magento/tree/1.1.13) (2018-12-11)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.12...1.1.13)

**Implemented enhancements:**

- Send subscription confirmation email via Magento [\#793](https://github.com/mailchimp/mc-magento/issues/793)
- Order confirmation email is bypassing Aschroder\_SMTPPro [\#673](https://github.com/mailchimp/mc-magento/issues/673)
- Add support for List Groups [\#514](https://github.com/mailchimp/mc-magento/issues/514)
- Add option to not send Promo Codes and Promo Rules [\#824](https://github.com/mailchimp/mc-magento/issues/824)
- Run webhook delete process more often for highly active stores. [\#818](https://github.com/mailchimp/mc-magento/issues/818)
- Change "MailChimp" appearances for "Mailchimp" [\#817](https://github.com/mailchimp/mc-magento/issues/817)
- Disable email catcher popup [\#816](https://github.com/mailchimp/mc-magento/issues/816)
- Error when a product has SKU = null [\#814](https://github.com/mailchimp/mc-magento/issues/814)
- Add option to create webhook manually [\#789](https://github.com/mailchimp/mc-magento/issues/789)
- Send abandoned carts from guest subscribers through campaign [\#766](https://github.com/mailchimp/mc-magento/issues/766)
- Bug in library with PHP7.x [\#763](https://github.com/mailchimp/mc-magento/issues/763)
- Show error message when MailChimp response does not exist anymore in their server. [\#753](https://github.com/mailchimp/mc-magento/issues/753)
- Remove addFilterToMap from order grid observer [\#744](https://github.com/mailchimp/mc-magento/issues/744)
- Avoid re-creating the store after Reset. [\#741](https://github.com/mailchimp/mc-magento/issues/741)
- Stop syncing process if no connection to MailChimp's API available. [\#738](https://github.com/mailchimp/mc-magento/issues/738)
- No Double Opt-in Option for MailChimp for Magento [\#727](https://github.com/mailchimp/mc-magento/issues/727)
- Capitalization at Newsletter top menu [\#718](https://github.com/mailchimp/mc-magento/issues/718)
- Add translations file. [\#689](https://github.com/mailchimp/mc-magento/issues/689)
- Remove disabled products to aovid using them in promotions. [\#582](https://github.com/mailchimp/mc-magento/issues/582)
- Send customers created in the backend [\#527](https://github.com/mailchimp/mc-magento/issues/527)
- Send most recently created promo codes first [\#736](https://github.com/mailchimp/mc-magento/pull/736) ([Leland](https://github.com/Leland))
- Index customer\_id on sales\_flat\_order [\#712](https://github.com/mailchimp/mc-magento/pull/712) ([pmclain](https://github.com/pmclain))

**Fixed bugs:**

- Growing DB Table `mailchimp\_webhook\_request` [\#812](https://github.com/mailchimp/mc-magento/issues/812)
- Total spent for customers sent incorrectly [\#791](https://github.com/mailchimp/mc-magento/issues/791)
- Error Report when Export Orders CSV or Excel [\#732](https://github.com/mailchimp/mc-magento/issues/732)
- Upgrade to 1.1.2 : You cannot define a correlation name 'mc' more than once [\#725](https://github.com/mailchimp/mc-magento/issues/725)
- When MailChimp site is down failures occur in the extension. [\#815](https://github.com/mailchimp/mc-magento/issues/815)
- Customer batch limit not working [\#806](https://github.com/mailchimp/mc-magento/issues/806)
- Change asynchronous execution of MailChimp JavaScript to deferred execution [\#804](https://github.com/mailchimp/mc-magento/issues/804)
- Show correct status for orders previous to first date [\#797](https://github.com/mailchimp/mc-magento/issues/797)
- Promo Rules with discount = 0 not syncing [\#777](https://github.com/mailchimp/mc-magento/issues/777)
- Resend Ecommerce Data not working [\#773](https://github.com/mailchimp/mc-magento/issues/773)
- Items marked with deleted\_related\_id incorrectly. [\#757](https://github.com/mailchimp/mc-magento/issues/757)
- Incorrect store domain when setting up at website level. [\#754](https://github.com/mailchimp/mc-magento/issues/754)
- Send products with no description available [\#747](https://github.com/mailchimp/mc-magento/issues/747)
- Synced status not reporting correctly in orders grid [\#726](https://github.com/mailchimp/mc-magento/issues/726)
- Clear mail object after sending message [\#719](https://github.com/mailchimp/mc-magento/issues/719)
- Token expiration in carts [\#714](https://github.com/mailchimp/mc-magento/issues/714)
- Add campaignCatcher.js file only if ecommerce is enabled. [\#698](https://github.com/mailchimp/mc-magento/issues/698)
- New Subscribers have no language in MC [\#695](https://github.com/mailchimp/mc-magento/issues/695)
- Set subcribers correct language in mailchimp during signup \#729 [\#730](https://github.com/mailchimp/mc-magento/pull/730) ([ljay79](https://github.com/ljay79))

## [1.1.12](https://github.com/mailchimp/mc-magento/tree/1.1.12) (2018-05-29)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.11...1.1.12)

**Implemented enhancements:**

- Request: add ability to send the actual BRAND/Manufacturer in the Vendor field [\#672](https://github.com/mailchimp/mc-magento/issues/672)
- Add new message for store creation error. [\#681](https://github.com/mailchimp/mc-magento/issues/681)
- Sort categories by name [\#659](https://github.com/mailchimp/mc-magento/issues/659)
- query optimizations 1 [\#583](https://github.com/mailchimp/mc-magento/issues/583)
- Add resend for subscriber data [\#482](https://github.com/mailchimp/mc-magento/issues/482)
- Subscriber resend issue482 [\#671](https://github.com/mailchimp/mc-magento/pull/671) ([Santiagoebizmarts](https://github.com/Santiagoebizmarts))
- add guest checkout name to subscriber data [\#664](https://github.com/mailchimp/mc-magento/pull/664) ([jan-lukowiak](https://github.com/jan-lukowiak))

**Fixed bugs:**

- Problem when updating customer email that is not subscribed [\#700](https://github.com/mailchimp/mc-magento/issues/700)
- STORECODE contains name of the store instead of code of the store [\#697](https://github.com/mailchimp/mc-magento/issues/697)
- Use store url with store code [\#691](https://github.com/mailchimp/mc-magento/issues/691)
- Compatibility issue with Ebizmarts\_SagePay when creating a new MailChimp store [\#680](https://github.com/mailchimp/mc-magento/issues/680)
- Checkout subscription not sending confirmation email if double opt-in enabled. [\#668](https://github.com/mailchimp/mc-magento/issues/668)
- Orders grid filter by increment ID is broken after upgrade to 1.1.11 [\#662](https://github.com/mailchimp/mc-magento/issues/662)
- Checkout subscription is only possible when isEcomSyncDataEnabled is enabled [\#657](https://github.com/mailchimp/mc-magento/issues/657)
- Wrong error management [\#635](https://github.com/mailchimp/mc-magento/issues/635)

## [1.1.11](https://github.com/mailchimp/mc-magento/tree/1.1.11) (2018-03-06)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.10...1.1.11)

**Implemented enhancements:**

- Load campaignCatcher.js async [\#624](https://github.com/mailchimp/mc-magento/issues/624)
- Improve performance when retrieving the last date of purchase [\#619](https://github.com/mailchimp/mc-magento/issues/619)
- add index [\#584](https://github.com/mailchimp/mc-magento/issues/584)
- Added Mailchimperrors grid column Created At [\#569](https://github.com/mailchimp/mc-magento/issues/569)
- Put a column in the order grid to show if the order was synced [\#557](https://github.com/mailchimp/mc-magento/issues/557)
- Send parent price for not visible products belonging to a configurable [\#538](https://github.com/mailchimp/mc-magento/issues/538)
- Check if webhook exists after batch process and create it if missing [\#535](https://github.com/mailchimp/mc-magento/issues/535)
- Ebizmarts\_MailChimp properties are not defined correctly [\#361](https://github.com/mailchimp/mc-magento/issues/361)
- Cache management page - Do not flush cache [\#594](https://github.com/mailchimp/mc-magento/pull/594) ([ihor-sviziev](https://github.com/ihor-sviziev))
- load MCJs async [\#546](https://github.com/mailchimp/mc-magento/pull/546) ([m-overlund](https://github.com/m-overlund))

**Fixed bugs:**

- Promo rules response handling incorrectly. [\#654](https://github.com/mailchimp/mc-magento/issues/654)
- Problem with migration when only configured in store view. [\#633](https://github.com/mailchimp/mc-magento/issues/633)
- Handle store name change correctly [\#629](https://github.com/mailchimp/mc-magento/issues/629)
- Error generating new Promo Codes Collection [\#620](https://github.com/mailchimp/mc-magento/issues/620)
- getResourceModel not working correctly in some installations [\#616](https://github.com/mailchimp/mc-magento/issues/616)
- Altering email address of customer results in "Call to member function on null" when no API key is configured [\#613](https://github.com/mailchimp/mc-magento/issues/613)
- Resend Ecommerce Data not working with promo rules and promo codes [\#607](https://github.com/mailchimp/mc-magento/issues/607)
- Promo code data in order not sent correctly to Mailchimp [\#591](https://github.com/mailchimp/mc-magento/issues/591)
- Unable to "Reset MailChimp Store" because running out of memory [\#590](https://github.com/mailchimp/mc-magento/issues/590)
- Small and thumbnail images not sent [\#589](https://github.com/mailchimp/mc-magento/issues/589)
- All orders marked with Mailchimp logo even if they're not coming from Mailchimp [\#576](https://github.com/mailchimp/mc-magento/issues/576)
- Child product update when parent has not been sent yet [\#575](https://github.com/mailchimp/mc-magento/issues/575)
- Images are not sent in certain versions of PHP [\#559](https://github.com/mailchimp/mc-magento/issues/559)
- When Mandrill disabled in default scope and enabled in certain store views email sending fails. [\#550](https://github.com/mailchimp/mc-magento/issues/550)
- When api key is changed deleteCurrentWebhook method fails [\#548](https://github.com/mailchimp/mc-magento/issues/548)
- Order grid: All orders shows the mailchimp logo [\#539](https://github.com/mailchimp/mc-magento/issues/539)
- Removes exception in order grid if for example a used payment method has [\#563](https://github.com/mailchimp/mc-magento/pull/563) ([freestream](https://github.com/freestream))

## [1.1.10](https://github.com/mailchimp/mc-magento/tree/1.1.10) (2017-10-31)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.9.1...1.1.10)

**Implemented enhancements:**

- Consider prices set per website when configured that way [\#511](https://github.com/mailchimp/mc-magento/issues/511)
- Feature Request: Small Image instead of Base Image [\#414](https://github.com/mailchimp/mc-magento/issues/414)
- Add support for Promo Rules and Promo Codes [\#515](https://github.com/mailchimp/mc-magento/issues/515)
- Image for simple products not showing when inherited from configurable [\#513](https://github.com/mailchimp/mc-magento/issues/513)
- Change display of total subscribers in account details. [\#502](https://github.com/mailchimp/mc-magento/issues/502)
- Create cron job to clean mailchimp\_webhook\_request table [\#460](https://github.com/mailchimp/mc-magento/issues/460)
- Unnecessary error reporting during user subscription [\#284](https://github.com/mailchimp/mc-magento/issues/284)

**Fixed bugs:**

- Check how is\_syncing flag is modified. [\#510](https://github.com/mailchimp/mc-magento/issues/510)
- Webhook not created when module configured on store view [\#508](https://github.com/mailchimp/mc-magento/issues/508)
- Remove old mcjs url to be replaced with the new one. [\#492](https://github.com/mailchimp/mc-magento/issues/492)
- Subscribe on Checkout doesn't send email to Mailchimp if already as customer on the list [\#484](https://github.com/mailchimp/mc-magento/issues/484)
- Order status not updated in Mailchimp [\#481](https://github.com/mailchimp/mc-magento/issues/481)
- Product categories not being sent to Mailchimp [\#476](https://github.com/mailchimp/mc-magento/issues/476)
- First Purchase Automation Not Triggering [\#453](https://github.com/mailchimp/mc-magento/issues/453)
- Product feed not working on multiple stores \(linking to default Mage store\) [\#442](https://github.com/mailchimp/mc-magento/issues/442)

## [1.1.9.1](https://github.com/mailchimp/mc-magento/tree/1.1.9.1) (2017-09-21)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.9...1.1.9.1)

## [1.1.9](https://github.com/mailchimp/mc-magento/tree/1.1.9) (2017-09-18)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.8...1.1.9)

**Implemented enhancements:**

- Resend ecommerce corrupted data [\#359](https://github.com/mailchimp/mc-magento/issues/359)
- Separate each address field when sending subscriber data [\#423](https://github.com/mailchimp/mc-magento/issues/423)
- Rename MailChimp\_Requests.log file to MailChimp\_Failing\_Requests.log and log subscriber failing requests. [\#417](https://github.com/mailchimp/mc-magento/issues/417)
- Show camp name in magento order view [\#416](https://github.com/mailchimp/mc-magento/issues/416)
- Create button to re-send ecommerce data without loosing MailChimp store. [\#413](https://github.com/mailchimp/mc-magento/issues/413)
- Simple products showing at $0 [\#370](https://github.com/mailchimp/mc-magento/issues/370)
- Is it possible to populate default language via Magento -\> MC [\#357](https://github.com/mailchimp/mc-magento/issues/357)
- Enable overriding e-commerce sync batch size [\#256](https://github.com/mailchimp/mc-magento/issues/256)

**Fixed bugs:**

- Fix for subscriber address. [\#478](https://github.com/mailchimp/mc-magento/issues/478)
- Deleting Newsletter subscribers in Magento cleans them in MailChimp [\#448](https://github.com/mailchimp/mc-magento/issues/448)
- Error with multi-currency for carts in multi-store [\#441](https://github.com/mailchimp/mc-magento/issues/441)
- Conflict with multi-currency for orders and revenue [\#439](https://github.com/mailchimp/mc-magento/issues/439)
- flag 'bad' addresses, and stop trying them. [\#436](https://github.com/mailchimp/mc-magento/issues/436)
- Send product data for the correct store view. [\#421](https://github.com/mailchimp/mc-magento/issues/421)
- mailchimp\_process\_webhook\_data Cron failures [\#415](https://github.com/mailchimp/mc-magento/issues/415)
- Unnecessary batch processing with empty batch\_id causes errors [\#404](https://github.com/mailchimp/mc-magento/issues/404)
- Parent product image doesn't update, only variant does [\#363](https://github.com/mailchimp/mc-magento/issues/363)
- Invalid product url on simple products not visible [\#341](https://github.com/mailchimp/mc-magento/issues/341)
- Address MERGE tags not created/synced [\#273](https://github.com/mailchimp/mc-magento/issues/273)

## [1.1.8](https://github.com/mailchimp/mc-magento/tree/1.1.8) (2017-07-27)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.7...1.1.8)

**Implemented enhancements:**

- Ignore already exists error [\#360](https://github.com/mailchimp/mc-magento/issues/360)
- Put webhook calls on a queue [\#267](https://github.com/mailchimp/mc-magento/issues/267)
- Add a PHP script to remove the extension, add it to the extension [\#137](https://github.com/mailchimp/mc-magento/issues/137)
- Add checkout subscription checkbox [\#405](https://github.com/mailchimp/mc-magento/issues/405)
- Change color of migration notice because red can be taken as an error. [\#385](https://github.com/mailchimp/mc-magento/issues/385)
- When item already exists asume it should be an edit request. [\#368](https://github.com/mailchimp/mc-magento/issues/368)
- Send out of stock products [\#353](https://github.com/mailchimp/mc-magento/issues/353)
- Modify webhook creation [\#340](https://github.com/mailchimp/mc-magento/issues/340)
- New Feature: flag is\_syncing  [\#323](https://github.com/mailchimp/mc-magento/issues/323)
- API Products constant array declaration unsupported in PHP 5.5 [\#316](https://github.com/mailchimp/mc-magento/issues/316)
- Set limit on Collection to 1. [\#333](https://github.com/mailchimp/mc-magento/pull/333) ([centerax](https://github.com/centerax))
- Do not translate customer group [\#315](https://github.com/mailchimp/mc-magento/pull/315) ([Schrank](https://github.com/Schrank))

**Fixed bugs:**

- Error on deleteStore function when removing old webhooks [\#407](https://github.com/mailchimp/mc-magento/issues/407)
- Set limit for migraiton from 1.1.6 [\#396](https://github.com/mailchimp/mc-magento/issues/396)
- If ecommerce section enabled but no Api key is set the extension tries to get the MCJS anyways [\#388](https://github.com/mailchimp/mc-magento/issues/388)
- Problem with order edit causing "Resource not found error" [\#373](https://github.com/mailchimp/mc-magento/issues/373)
- Catalog product flat table config causes problem when processing ecommerce data [\#369](https://github.com/mailchimp/mc-magento/issues/369)
- When ecommerce data is not enabled mcminsyncdateflag is empty affecting subscribers [\#364](https://github.com/mailchimp/mc-magento/issues/364)
- When ecommerce is not enabled can not reset errors. [\#349](https://github.com/mailchimp/mc-magento/issues/349)
- Duplicate entries for subscriber table when customer/subscriber created from admin [\#342](https://github.com/mailchimp/mc-magento/issues/342)
- Custom Product causing failure in SendModifiedProduct [\#335](https://github.com/mailchimp/mc-magento/issues/335)
- email index query is incorrect \(mysql4-upgrade-1.1.6.6-1.1.6.7.php\) [\#324](https://github.com/mailchimp/mc-magento/issues/324)
- PHP Fatal error in syncSubscriberBatchData \(cron\) [\#312](https://github.com/mailchimp/mc-magento/issues/312)
- Error Synchronising Products When Configurable Products Children Have Been Deleted [\#297](https://github.com/mailchimp/mc-magento/issues/297)
- Web hooks continuously processed [\#295](https://github.com/mailchimp/mc-magento/issues/295)
- Webhook process might fail if the configured list changes. [\#293](https://github.com/mailchimp/mc-magento/issues/293)
- Parent configurable images not being sent when child has no image. [\#292](https://github.com/mailchimp/mc-magento/issues/292)

## [1.1.7](https://github.com/mailchimp/mc-magento/tree/1.1.7) (2017-06-01)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.6...1.1.7)

**Implemented enhancements:**

- Send order id in stead of increment id in operation id for batches. [\#286](https://github.com/mailchimp/mc-magento/issues/286)
- Sent orderId in stead of incrementId in operation id in Orders.php [\#281](https://github.com/mailchimp/mc-magento/issues/281)
- Split cron jobs. [\#277](https://github.com/mailchimp/mc-magento/issues/277)
- Remove old MageMonkey webhooks. [\#261](https://github.com/mailchimp/mc-magento/issues/261)
- Handle data migration within a cron job in order to prevent problems during update. [\#233](https://github.com/mailchimp/mc-magento/issues/233)
- Installation of MC.js pixel [\#225](https://github.com/mailchimp/mc-magento/issues/225)
- Add customer id to mailchimp\_merge\_field\_send\_before observer [\#221](https://github.com/mailchimp/mc-magento/issues/221)
- Cache check for mailchimp store for given scope [\#216](https://github.com/mailchimp/mc-magento/issues/216)
- Incorrect log file referenced in configuration note [\#212](https://github.com/mailchimp/mc-magento/issues/212)
- Send store domain when creating it. [\#205](https://github.com/mailchimp/mc-magento/issues/205)
- Missing index on mailchimp\_ecommerce\_sync\_data [\#197](https://github.com/mailchimp/mc-magento/issues/197)
- Order ID being used instead of Order \# [\#165](https://github.com/mailchimp/mc-magento/issues/165)
- Fix case mixing and make class namespace/prefix the same for all classes [\#207](https://github.com/mailchimp/mc-magento/pull/207) ([kim-sondrup](https://github.com/kim-sondrup))
- Error in `getBatchResponse` method when `/var/mailchimp` directory does not exist [\#203](https://github.com/mailchimp/mc-magento/pull/203) ([vseager](https://github.com/vseager))
- Added pagination for too huge collections in data-upgrade [\#192](https://github.com/mailchimp/mc-magento/pull/192) ([loburets](https://github.com/loburets))

**Fixed bugs:**

- Webhook calls cause unnecessary calls when handleSubscriber method is called from webhook [\#279](https://github.com/mailchimp/mc-magento/issues/279)
- Migration never ends due to cron failure [\#266](https://github.com/mailchimp/mc-magento/issues/266)
- The parent product must already exists in order to use PUT on the variants endpoint error in some installations. [\#254](https://github.com/mailchimp/mc-magento/issues/254)
- Can't change attribute or status of multiple products [\#241](https://github.com/mailchimp/mc-magento/issues/241)
- All orders are marked with landing page & as coming from MailChimp. [\#239](https://github.com/mailchimp/mc-magento/issues/239)
- mailchimp/api\_subscribers-\>\_getMCStatus\(\) returns integers [\#235](https://github.com/mailchimp/mc-magento/issues/235)
- Notice: Undefined index: image\_url [\#231](https://github.com/mailchimp/mc-magento/issues/231)
- Mailchimp store is created multiple times when enabling mailchimp and ecommerce data [\#227](https://github.com/mailchimp/mc-magento/issues/227)
- mailchimp\_campaign\_id not being saved if utm\_source=mailchimp not available. [\#226](https://github.com/mailchimp/mc-magento/issues/226)
- Don't skip store subscriber changes if previous store has no changes to synchronise [\#222](https://github.com/mailchimp/mc-magento/issues/222)
- Fixes for cart changes not being uploaded for abandoned cart [\#219](https://github.com/mailchimp/mc-magento/issues/219)
- Multi-store abandoned cart enabled flag ignored [\#218](https://github.com/mailchimp/mc-magento/issues/218)
- Fix for invalid list ID when saving mailchimp system configuration [\#214](https://github.com/mailchimp/mc-magento/issues/214)
- Line feeds in default configuration values in config.xml break unserialize [\#213](https://github.com/mailchimp/mc-magento/issues/213)
- Subscription fails when a customer has wrong address data, infinite loop [\#211](https://github.com/mailchimp/mc-magento/issues/211)
- Bulk Editing Products Returns Blank Error \[Fix inside\] [\#209](https://github.com/mailchimp/mc-magento/issues/209)
- PHP Fatal error: Call to a member function getStreet\(\) on a non-object in app/code/community/Ebizmarts/MailChimp/Model/Api/Orders.php on line 321 [\#208](https://github.com/mailchimp/mc-magento/issues/208)
- Could not delete customer. [\#206](https://github.com/mailchimp/mc-magento/issues/206)
- Exception is thrown when trying to update product status from a script [\#204](https://github.com/mailchimp/mc-magento/issues/204)
- Merge fields not pushed on customer save [\#201](https://github.com/mailchimp/mc-magento/issues/201)
- Fatal Error in handleSubscriberDeletion\(\) method from Observer.php [\#195](https://github.com/mailchimp/mc-magento/issues/195)
- 1.5.5.6-1.5.6 MySQL upgrade memory exhausted [\#189](https://github.com/mailchimp/mc-magento/issues/189)
- Subscriber batches remain in pending state [\#187](https://github.com/mailchimp/mc-magento/issues/187)
- Minor issue with cron [\#186](https://github.com/mailchimp/mc-magento/issues/186)
- Syncing customer billing/shipping address fields does not work [\#184](https://github.com/mailchimp/mc-magento/issues/184)
- Get API Credential - Back End [\#179](https://github.com/mailchimp/mc-magento/issues/179)

## [1.1.6](https://github.com/mailchimp/mc-magento/tree/1.1.6) (2017-03-30)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.5...1.1.6)

**Implemented enhancements:**

- Add full support for multi-stores. [\#103](https://github.com/mailchimp/mc-magento/issues/103)
- Create event to handle custom merge fields [\#176](https://github.com/mailchimp/mc-magento/issues/176)
- Unable to send email in queue unless entity\_type = 'order'.... [\#174](https://github.com/mailchimp/mc-magento/issues/174)
- Set the DOB field to be created as birthday on MailChimp. [\#173](https://github.com/mailchimp/mc-magento/issues/173)
- customer re-subscribe fails silently [\#167](https://github.com/mailchimp/mc-magento/issues/167)
- No redirect back from customer login when accessing abandoned cart URL [\#162](https://github.com/mailchimp/mc-magento/issues/162)
- Recommend products no images when only configurable product has images [\#140](https://github.com/mailchimp/mc-magento/issues/140)

**Fixed bugs:**

- Cart Url redirect failing. [\#180](https://github.com/mailchimp/mc-magento/issues/180)
- Response downloads are always empty [\#177](https://github.com/mailchimp/mc-magento/issues/177)
- Merge Fields not updated in Mailchimp [\#170](https://github.com/mailchimp/mc-magento/issues/170)
- Send e-mail copy type "Separate Email" bug [\#163](https://github.com/mailchimp/mc-magento/issues/163)
- Admin skin missing a file [\#156](https://github.com/mailchimp/mc-magento/issues/156)
- Move debug scripts [\#155](https://github.com/mailchimp/mc-magento/issues/155)
- Guest orders are not synced [\#150](https://github.com/mailchimp/mc-magento/issues/150)
- Integrity constraint violation when syncing e-commerce data [\#147](https://github.com/mailchimp/mc-magento/issues/147)
- Lower case subscribers class name [\#145](https://github.com/mailchimp/mc-magento/issues/145)

## [1.1.5](https://github.com/mailchimp/mc-magento/tree/1.1.5) (2017-02-08)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.3...1.1.5)

**Implemented enhancements:**

- Typo in Configuration header [\#121](https://github.com/mailchimp/mc-magento/issues/121)
- Allow custom mailchimp attributes to be deleted from back end. [\#119](https://github.com/mailchimp/mc-magento/issues/119)
- If the recipient doesn't exists in the email queue skip it [\#118](https://github.com/mailchimp/mc-magento/issues/118)
- Add first date for orders [\#113](https://github.com/mailchimp/mc-magento/issues/113)
- Pass order\_URL for orders [\#135](https://github.com/mailchimp/mc-magento/issues/135)
- Need to pass Shipping and Billing Addresses for Orders [\#128](https://github.com/mailchimp/mc-magento/issues/128)
- Populate landing\_site column [\#123](https://github.com/mailchimp/mc-magento/issues/123)
- Update order status [\#120](https://github.com/mailchimp/mc-magento/issues/120)
- Get URL for MailChimp store based on configurations in stead of current URL [\#115](https://github.com/mailchimp/mc-magento/issues/115)

**Fixed bugs:**

- The product images link to my administrator page, not to the front-end of my magento's website. [\#127](https://github.com/mailchimp/mc-magento/issues/127)
- The download link in the error grid doesn't work [\#126](https://github.com/mailchimp/mc-magento/issues/126)
- The table sales\_flat\_quote don't content the field mailchimp\_campaign\_id [\#125](https://github.com/mailchimp/mc-magento/issues/125)
- If the batch id doesn't exists when retrieving batch responses the process stops. [\#116](https://github.com/mailchimp/mc-magento/issues/116)
- Wrong format for mailchimp\_sync\_delta field [\#111](https://github.com/mailchimp/mc-magento/issues/111)
- Carts with country data send country name on country code field and vice versa. [\#108](https://github.com/mailchimp/mc-magento/issues/108)
- Calling $object-\>save\(\) on entities during batch processing [\#88](https://github.com/mailchimp/mc-magento/issues/88)
- Make sure cancelled orders go to Cancelled, not Pending [\#133](https://github.com/mailchimp/mc-magento/issues/133)
- Store name changes not pushed up to MailChimp [\#130](https://github.com/mailchimp/mc-magento/issues/130)
- Wrong Store Name [\#129](https://github.com/mailchimp/mc-magento/issues/129)

## [1.1.3](https://github.com/mailchimp/mc-magento/tree/1.1.3) (2016-12-15)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.2...1.1.3)

**Implemented enhancements:**

- The Monkey image in the order grid [\#107](https://github.com/mailchimp/mc-magento/issues/107)
- Do not update the status for already subscribed customers in MailChimp when syncing for the first time. [\#102](https://github.com/mailchimp/mc-magento/issues/102)
- Change the with of the Mailchimp column in the order grid [\#101](https://github.com/mailchimp/mc-magento/issues/101)
- Error grid sohwing Id for better debugging. [\#100](https://github.com/mailchimp/mc-magento/issues/100)
- Swap lines in Configuration page [\#99](https://github.com/mailchimp/mc-magento/issues/99)

**Fixed bugs:**

- Error in the lib [\#106](https://github.com/mailchimp/mc-magento/issues/106)
- Check for customer data [\#105](https://github.com/mailchimp/mc-magento/issues/105)
- Stores with long domain name doesn't create properly in Ecommerce [\#85](https://github.com/mailchimp/mc-magento/issues/85)

## [1.1.2](https://github.com/mailchimp/mc-magento/tree/1.1.2) (2016-10-25)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.1...1.1.2)

**Implemented enhancements:**

- Add is\_syncing flag usage for MailChimp store. [\#80](https://github.com/mailchimp/mc-magento/issues/80)
- Abandoned cart in sales order grid [\#77](https://github.com/mailchimp/mc-magento/issues/77)
- Allow store owners to decide if customers will be subscribed to the newsletter. [\#75](https://github.com/mailchimp/mc-magento/issues/75)
- Ecommerce data saving in website and store scopes [\#74](https://github.com/mailchimp/mc-magento/issues/74)
- Make the order to send your own products [\#67](https://github.com/mailchimp/mc-magento/issues/67)
- Utilty to download the batch response [\#66](https://github.com/mailchimp/mc-magento/issues/66)
- Add the Batch Id to the mailchimp error grid [\#65](https://github.com/mailchimp/mc-magento/issues/65)
- Generate one log per each batch [\#64](https://github.com/mailchimp/mc-magento/issues/64)
- Add composer.json and modman support [\#61](https://github.com/mailchimp/mc-magento/issues/61)

**Fixed bugs:**

- Error message "Error: no identification SUB found" solved. [\#76](https://github.com/mailchimp/mc-magento/issues/76)
- Carts being sent even if disabled in the configuration. [\#73](https://github.com/mailchimp/mc-magento/issues/73)
- Invalid country code error shown in MailChimp\_Errors.log [\#72](https://github.com/mailchimp/mc-magento/issues/72)
- Customers generating resource not found error [\#71](https://github.com/mailchimp/mc-magento/issues/71)
- Carts not existing on MailChimp being deleted before getting sent. [\#70](https://github.com/mailchimp/mc-magento/issues/70)
- campaign\_id isn't associated to order when cookie lifetime != 3600 [\#68](https://github.com/mailchimp/mc-magento/issues/68)
- Update product stock qty [\#56](https://github.com/mailchimp/mc-magento/issues/56)

## [1.1.1](https://github.com/mailchimp/mc-magento/tree/1.1.1) (2016-09-13)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.1.0...1.1.1)

**Fixed bugs:**

- Mixed emails sent when made simultaneously on checkout. [\#60](https://github.com/mailchimp/mc-magento/issues/60)

## [1.1.0](https://github.com/mailchimp/mc-magento/tree/1.1.0) (2016-09-13)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.6...1.1.0)

**Implemented enhancements:**

- Add permission functionality for back end controllers [\#53](https://github.com/mailchimp/mc-magento/issues/53)
- Remove unnecessary menu option [\#52](https://github.com/mailchimp/mc-magento/issues/52)
- Add MC logo to orders table for orders made from a Campaign [\#51](https://github.com/mailchimp/mc-magento/issues/51)
- Add link to MailChimp For Magento docs [\#50](https://github.com/mailchimp/mc-magento/issues/50)

**Fixed bugs:**

- Json enconde error [\#59](https://github.com/mailchimp/mc-magento/issues/59)
- Tier prices being deleted after products being sent. [\#57](https://github.com/mailchimp/mc-magento/issues/57)
- Sync process stops randomly and does not go ahead [\#55](https://github.com/mailchimp/mc-magento/issues/55)
- Sending products to Mailchimp makes Dropdown attributes to get the "Default option" \(if have one selected\) [\#54](https://github.com/mailchimp/mc-magento/issues/54)

## [1.0.6](https://github.com/mailchimp/mc-magento/tree/1.0.6) (2016-08-17)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.4.2...1.0.6)

**Implemented enhancements:**

- Message when can't create a webhook [\#37](https://github.com/mailchimp/mc-magento/issues/37)
- Sent link to list creation page when no list available [\#36](https://github.com/mailchimp/mc-magento/issues/36)
- Hide Merge Fields [\#43](https://github.com/mailchimp/mc-magento/issues/43)
- Abandoned Guest checkouts using Subscribed email addresses not passed to MC [\#40](https://github.com/mailchimp/mc-magento/issues/40)
- Include address information for guests on abandoned carts [\#32](https://github.com/mailchimp/mc-magento/issues/32)
- Tax and Shipping totals not passed to MailChimp [\#25](https://github.com/mailchimp/mc-magento/issues/25)
- Send carts [\#10](https://github.com/mailchimp/mc-magento/issues/10)
- Manage cancelled orders [\#4](https://github.com/mailchimp/mc-magento/issues/4)

**Fixed bugs:**

- Fix ApiKey and General Subscription List [\#41](https://github.com/mailchimp/mc-magento/issues/41)
- A magento report is generated when put an invalid ApiKey [\#39](https://github.com/mailchimp/mc-magento/issues/39)
- Handle campaignId when API Key/List changed. [\#38](https://github.com/mailchimp/mc-magento/issues/38)
- Issue with old cookie of the campaign [\#35](https://github.com/mailchimp/mc-magento/issues/35)
- Delete all carts for an email [\#31](https://github.com/mailchimp/mc-magento/issues/31)
- No send empty carts [\#30](https://github.com/mailchimp/mc-magento/issues/30)
- Not send guest carts for registered customer [\#29](https://github.com/mailchimp/mc-magento/issues/29)
- Old carts are sent [\#28](https://github.com/mailchimp/mc-magento/issues/28)
- Cron breaks when the email is entered in the popup [\#49](https://github.com/mailchimp/mc-magento/issues/49)
- Missing cache breaks Webhooks [\#48](https://github.com/mailchimp/mc-magento/issues/48)
- Manage the Ecommerce Enabled [\#47](https://github.com/mailchimp/mc-magento/issues/47)
- Subscribing, unsubscribing and subscribing again error. [\#46](https://github.com/mailchimp/mc-magento/issues/46)
- Handle total\_spent for MailChimp customers from Magento [\#45](https://github.com/mailchimp/mc-magento/issues/45)
- Handle order\_count for MailChimp customers from Magento [\#44](https://github.com/mailchimp/mc-magento/issues/44)
- Orders not sending all the customer information for guests [\#42](https://github.com/mailchimp/mc-magento/issues/42)
- Issue with new products [\#27](https://github.com/mailchimp/mc-magento/issues/27)
- Remove mailchimp cookie when new order is created [\#26](https://github.com/mailchimp/mc-magento/issues/26)

## [1.0.4.2](https://github.com/mailchimp/mc-magento/tree/1.0.4.2) (2016-07-04)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.4.1...1.0.4.2)

## [1.0.4.1](https://github.com/mailchimp/mc-magento/tree/1.0.4.1) (2016-07-01)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.4...1.0.4.1)

## [1.0.4](https://github.com/mailchimp/mc-magento/tree/1.0.4) (2016-07-01)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.3...1.0.4)

**Implemented enhancements:**

- Customer Modification [\#24](https://github.com/mailchimp/mc-magento/issues/24)
- Pass order information if a product type is not supported [\#18](https://github.com/mailchimp/mc-magento/issues/18)

**Fixed bugs:**

- opt\_in\_status is always sent as FALSE [\#15](https://github.com/mailchimp/mc-magento/issues/15)
- Issue with the stock when save a product [\#16](https://github.com/mailchimp/mc-magento/issues/16)

## [1.0.3](https://github.com/mailchimp/mc-magento/tree/1.0.3) (2016-06-20)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.2...1.0.3)

## [1.0.2](https://github.com/mailchimp/mc-magento/tree/1.0.2) (2016-06-14)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.1...1.0.2)

**Implemented enhancements:**

- Change array declaration to pre php 5.4. [\#3](https://github.com/mailchimp/mc-magento/issues/3)

## [1.0.1](https://github.com/mailchimp/mc-magento/tree/1.0.1) (2016-06-08)
[Full Changelog](https://github.com/mailchimp/mc-magento/compare/1.0.0...1.0.1)

## [1.0.0](https://github.com/mailchimp/mc-magento/tree/1.0.0) (2016-06-06)


\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*
