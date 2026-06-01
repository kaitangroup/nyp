=== Packlink PRO for WooCommerce ===
Contributors: packlink
Tags: shipping, delivery, carrier, order, package
Requires at least: 4.7
Requires PHP: 7.0
Tested up to: 6.9
Stable tag: 4.1.2
License: LICENSE-2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0

Elevate your store with Packlink PRO —the ultimate free shipping solution offering discounted rates from 350+ carriers instantly.

== Description ==

**Installation and Configuration Video**

<a href="https://youtu.be/uwI-zArAKxo" target="_blank" title="How to connect your WooCommerce shop">Full installation & configuration guide on YouTube</a>

=== Lower Your Shipping Costs & Automate Orders with Packlink PRO ===

Boost Your WooCommerce Shipping with Packlink PRO

Take your WooCommerce store to the next level with Packlink PRO—your ultimate shipping solution. Instantly access discounted rates from 350+ top carriers without the hassle of contracts or volume commitments.

And the best part? Packlink PRO is 100% free to use!

**Why Choose Packlink PRO?**
<ul>
<li>Save up to 70% on shipping costs with exclusive carrier discounts.</li>
<li>Automate & streamline your shipping—import orders in real-time from WooCommerce and leading marketplaces.</li>
<li>Print bulk shipping labels at discounted rates.</li>
<li>Keep customers informed with real-time tracking updates—all from one easy-to-use dashboard.</li>
</ul>

Join 20,000+ online stores already using Packlink PRO and start optimizing your shipping today!

**Key features:**
<ul>
<li><strong>Seamless Order Import:</strong> Sync WooCommerce orders in real-time.</li>
<li><strong>Multi-Marketplace Integration:</strong> Connect with top marketplaces effortlessly.</li>
<li><strong>Exclusive Carrier Discounts:</strong> Save on 350+ national & international shipping options, including express, standard, drop-off, and pick-up services.</li>
<li><strong>Smart Shipping Automation:</strong> Set custom shipping rules to streamline your workflow.</li>
<li><strong>Bulk & One-Click Label Printing:</strong> Print labels individually or in bulk with ease.</li>
<li><strong>Real-Time Order Tracking:</strong> Keep customers updated and boost satisfaction.</li>
<li><strong>Dedicated Local Support:</strong> Get expert help from a single point of contact.</li>
</ul>

**<a href="https://auth.packlink.com/en-GB/woocommerce/register" target="_blank" title="Subscription">Register free</a> in Packlink PRO and get started!**


== Installation ==

This is how the WooCommerce integration with Packlink PRO works.

**1. Install and configure the plugin**

- You can install the Packlink PRO plugin in one of two ways:
  - Option a. From your WordPress back office go to "Plugins" > "Add new" > then, search for "Packlink" > "Install now".
  - Option b. Go to <a href="https://wordpress.org/plugins/packlink-pro-shipping">https://wordpress.org/plugins/packlink-pro-shipping</a> and click on the "Download" button. Then, from your WordPress back office "Plugins" section click on "Add new" > "Upload plugin" and upload the downloaded zip file.

- Once you have installed the plugin, login to the Packlink PRO website and click on "Configuration" in the top right-hand corner.

- From the left-hand menu, select "Connect your shop" and click on the WooCommerce logo to generate the API key to synchronize both platforms.

- Copy this API key in Packlink PRO module in WooCommerce.

- You can define the dimensions of your most common parcels and pickup address. This information is automatically synchronized in WooCommerce.

**2. Sync with your Packlink PRO account**

- In WooCommerce back office, select WooCommerce > Packlink PRO from the left-hand menu.

- Paste the API key you copied from your Packlink PRO account and click on the Log in button.

- The module will automatically synchronize your default parcel dimensions, pickup address, and shipping services from Packlink PRO (this process can take several minutes).

- Select the shipping services you want to use. You can configure the name of each service and whether you show the carrier logo to your customers.

- You can define your pricing policy by choosing from the following options: direct Packlink prices, percentage of Packlink price, fixed price by weight, or fixed price by shopping cart.


**3. Use the module**

- If an order has been paid or payment was accepted by you, the shipment will be automatically imported into your Packlink PRO account.

- You have the option to manually send an order to Packlink PRO by opening order details page and clicking in "Create draft" in the "Packlink PRO Shipping" section on the right side.

- Packlink PRO is always updated with all shipments that are ready for shipment in WooCommerce.

- You only need to access Packlink PRO for payment. Sender and recipient details are synchronized with WooCommerce data.

Click <a href="https://support-pro.packlink.com/hc/es-es/articles/210158585" target="_blank" title="support">here</a> to get more information about the installation.


== Changelog ==

#### 4.1.2 - May 19th, 2026

**Updates**

- Fix PHP notice about textdomain being loaded too early in WordPress 6.7+
- Fix drop-off translations being overridden by incomplete auto-downloaded translations

#### 4.1.1 - April 29th, 2026

**Updates**

- Fix default integration name
- Fix print labels bulk action

#### 4.1.0 - April 21st, 2026

**Updates**

- Added store registration for Packlink
- Fixed missing translations for drop-off

#### 4.0.1 - March 19th, 2026

**Updates**
* Corrected service ID sent to the Packlink API
* Improved postal code handling for AE
* Improved drop-off button rendering
* Fix send draft when manual sync is enabled
* Added caching for shipping calculation parameters

#### 4.0.0 - March 10th, 2026

**Updates**
* Dropped support for PHP 5.6. Minimum supported PHP version is now PHP 7.0
* Migrated Packlink task execution to WooCommerce Action Scheduler
* Replaced legacy TaskRunner/Queue implementation with the new TaskExecutor infrastructure from Core

#### 3.6.5 - March 9th, 2026

**Updates**
- Transformations applied to GR and LV postal codes

#### 3.6.4 - February 4th, 2026

**Updates**
- Rates for AU, CA, and US fixed
- Shop manager enabled to create draft
- Weight unit mismatch fixed
- Duplicate SQL queries fixed

#### 3.6.3 - January 21st, 2026

**Updates**
- Fix downloadable products no longer blocked for shipping
- Fix translations (DE, IT, ES, FR) when drop-off selected

#### 3.6.2 - January 15th, 2026

**Updates**
- Fix error on filtering payment gateways

#### 3.6.1 - November 12th, 2025

**Updates**
- Fix drop-off button rendering on the checkout
- Add fallback mehnism for fetching cart subtotal

#### 3.6.0 - October 16th, 2025

**Updates**
- Added support for cash on delivery

#### 3.5.6 - August 5th, 2025

**Updates**
- Modify shipping services availability at checkout

#### 3.5.5 - July 23th, 2025

**Updates**
- Fix issue with empty shipping services list
- Fix fatal error on older WooCommerce versions due to incompatible ProductTaxStatus usage

#### 3.5.4 - June 17th, 2025

#### 3.5.3 - June 11th, 2025

**Updates**
- Fix the issue with taxes during order creation when Block Checkout is enabled

#### 3.5.2 - May 26th, 2025

**Updates**
- Fix incorrect mapping of tags in inflate method

#### 3.5.1 - April 10th, 2025

**Updates**
- Add security improvements

#### 3.5.0 - March 27th, 2025
**Updates**
Add support for special services and manual refresh of shipping services

#### 3.4.16 - March 12th, 2025

**Updates**
- Add unsupported countries Morocco, United Arab Emirates and Monaco

#### 3.4.15 - February 21st, 2025

**Updates**
- Fix problem with state

#### 3.4.14 - January 22nd, 2025

**Updates**
- Fix problem with CustomOrdersTableController existence check

#### 3.4.13 - October 23rd, 2024

**Updates**
- Add validation to prevent shipping of orders with only virtual or/and downloadable products and display an error message

#### 3.4.12 - September 30th, 2024

**Updates**
- Mark compatibility with WooCommerce 9.3.1 and WordPress 6.6.2

#### 3.4.11 - August 27th, 2024

**Updates**
- Fix setting async request timeout using support controller
- Mark compatibility with WooCommerce 9.1.4 and WordPress 6.6.1

#### 3.4.10 - July 29th, 2024

**Updates**
- Fix the issue with the drop-off button not displayed on the checkout

#### 3.4.9 - July 3th, 2024

**Updates**
- Fix the issue with the drop-off button at the top of the page
- Fix the issue where the button no longer appeared after making changes on the checkout
- Add translations for default shipping cost
- Update compatible version of WooCommerce (9.0.2) and WordPress (6.5.5)
- Add unsupported country Algeria

#### 3.4.8 - June 5th, 2024

**Updates**
- Update compatible version of WooCommerce (8.9.1) and WordPress (6.5.3)

#### 3.4.7 - May 20th, 2024

**Updates**
- Important security enhancements and fixes

#### 3.4.6 - May 15th, 2024

**Updates**
- Fix the issue with deleted shipping zones
- Fix the issue of updating shipping methods that are already updated

#### 3.4.5 - April 11th, 2024

**Updates**
- Add unsupported countries Denmark, Norway, Saudi Arabia, Canada, Cyprus, Slovenia and Slovakia
- Set minimum height for the location picker popup

#### 3.4.4 - March 26th, 2024

**Updates**
- Optimize checkout flow for picking drop off location

#### 3.4.3 - March 11th, 2024

**Updates**
- Add unsupported countries: Estonia, Latvia and Romania

#### 3.4.2 - February 26th, 2024

**Updates**
- Add unsupported countries (Bulgaria, Finland, Greece, Australia)
- Add French overseas territories
- Fix displaying error message on the checkout page

#### 3.4.1 - January 17th, 2024

**Updates**
- Fix rendering order page
- Fix updating 'Send with Packlink' button

#### 3.4.0 - January 11th, 2024

**Updates**
- Add compatability with WooCommerce block checkout enhancement
- Update compatible version of WooCommerce (8.5.0) and WordPress (6.4.2)

#### 3.3.4 - November 21st, 2023

**Updates**
- Updated to the latest Core changes regarding shipping cost calculations.

#### 3.3.3 - October 19th, 2023

**Updates**
- Fix different logo image size on order page
- Update compatible version of WooCommerce (8.2.1) and WordPress (6.3.2)

#### 3.3.2 - October 17th, 2023

**Updates**
- Fix the issue with drop off selection
- Update compatible version of WooCommerce (8.2)

#### 3.3.1 - October 11th, 2023

**Updates**
- Add compatibility with WooCommerce 8.1.1 and WordPress 6.3.1

#### 3.3.0 - July 24th, 2023

**Updates**
- Add compatibility with WooCommerce HPOS (High-Performance Order Storage) feature

#### 3.2.18 - July 19th, 2023

**Updates**
- Fix issues with relay points
- Update phone number validation

#### 3.2.17 - June 6, 2023

**Updates**
- Update link to order draft on Packlink

#### 3.2.16 - May 30, 2023

**Updates**
- Fix view on Packlink link

#### 3.2.15 - May 18, 2023

**Updates**
- Fix layout on orders page

#### 3.2.14 - March 8, 2023

**Updates**
- Add handling of shipment delivered webhook.

#### 3.2.13 - December 13, 2022

**Updates**
- Fix duplicating shipping methods.

#### 3.2.12 - October 10, 2022

**Updates**
- Stabilize version.

#### 3.2.10 - July 19, 2022

**Updates**
- Added compatibility with the new checkout page.

#### 3.2.9 - May 30, 2022

**Updates**
- Updated async process wakeup delay for manual sync.

#### 3.2.8 - May 10, 2022

**Updates**
- Added carrier logos for Colis Prive and Shop2Shop shipping services.

#### 3.2.7 - April 12, 2022

**Updates**
- Optimized order sync for users experiencing CPU surcharge by adding an option to switch to manual synchronization.

#### 3.2.6 - February 17, 2022

**Updates**
- Updated to the latest Core changes regarding changing the value of the marketing calls flag.
- Updated compatible versions of WordPress (5.9.0) and WooCommerce (6.1.0).

#### 3.2.5 - December 7, 2021

**Updates**
- Added configuration for page footer height.
- Fixed shipping cost calculation.

#### 3.2.4 - August 31, 2021

**Updates**
- Add order reference sync.
- Add support for additional statuses.

#### 3.2.0 - July 07, 2021

**Updates**

- Add support for multi-currency.

#### 3.1.3 - March 01, 2021

**Updates**

- Preserve shipping class costs configuration when updating Packlink carriers.
- Remove notifications on the configuration page.
- Fix order status cancelled update.

#### 3.1.2 - December 21, 2020

**Updates**

- Add migration script to fix the saved parcel.

#### 3.1.0 - December 17, 2020

**Updates**

- Added postal code transformer that transforms postal code into supported postal code format for GB, NL, US and PT countries.
- Added support for new warehouse countries.

#### 3.0.7 - November 11, 2020

**Updates**

- Fix issue with execution of queue items.

#### 3.0.6 - November 10, 2020

**Updates**

- Add missing HERMES and DPD carrier logos.
- Fix warnings on the cart page.
- Fix setting warehouse postal code and city from the module.

#### 3.0.5 - October 21, 2020

**Updates**

- Add sending "disable_carriers" analytics.

#### 3.0.4 - September 28, 2020

**Updates**

- Check whether Packlink object is defined before initializing checkout script.
- Fix error when plugin translations for a language don't exist.

#### 3.0.3 - September 04, 2020

**Updates**

- Fixed location picker issue.

#### 3.0.2 - September 02, 2020

**Updates**

- Fixed translation issue in Italian.

#### 3.0.1

**Updates**

- Fixed changing shop order status upon shipment status update.

#### 3.0.0 - August 26, 2020

**Updates**

- New module design with new pricing policy.

#### 2.2.4

**Updates**

- Added support for the network activated WooCommerce plugin.
- Added Hungary to the list of supported countries.
- Fixed not saved drop-off point details on the order.

#### 2.2.3

**Updates**

- Added "Send with Packlink" button on order overview page.

#### 2.2.2

**Updates**

- Added top margin to drop-off button on checkout page.

#### 2.2.1

**Updates**

- Prevented export of order with no shippable products.
- Fixed order export if orders are not made with Packlink shipping method.
- Fixed the mechanism for updating information about created shipments.

#### 2.1.2

**Updates**

- Fixed the mechanism for updating information about created shipments.

#### 2.1.1

**Updates**

- Allow setting the lowest boundary for fixed price policies per shipping method.
- Changed the update interval for getting shipment data from Packlink API.
- Updated compatibility with the latest WP and WC versions

#### 2.1.0

**Updates**

- Added automatic re-configuration of the module based on WooCommerce and WordPress settings in cases when the module cannot run with the default shop and server settings.

#### 2.0.9

**Updates**

- Fixed compatibility bug with the WooCommerce prior to 3.0.4 for order shipping and billing addresses.

#### 2.0.8

**Updates**

- Fixed compatibility bug with the PHP versions prior to 5.5.

#### 2.0.7

**Updates**

- Fixed compatibility bug with the WooCommerce prior to 3.2 for shipment methods that require drop-off location.

#### 2.0.6

**Updates**

- Fixed backward compatibility with the WooCommerce prior to 3.2
- Fixed problem in updating shipping information from Packlink

#### 2.0.5

**Updates**

- Added new registration links
- Fixed some CSS issues

#### 2.0.4

**Updates**

- Added update message mechanism
- Minor bug fixes

#### 2.0.3

**Updates**

- The Add-on configuration page is completely redesigned with advanced options
- Added possibility for admin to enable only specific shipping services for end customers
- Each shipping service can be additionally configured by admin - title, logo display, advanced pricing policy configuration
- Enhanced integration with Packlink API
- End customers can now select a specific drop-off point for such shipping services during the checkout process
- Order list now has information about Packlink shipments and option to print shipping labels directly from the order list
- Order details page now contains more information about each shipment

#### 1.0.2

**Updates**

- Tested up to: 4.9.1

#### 1.0.0

**Updates**

- Initial version.
