=== Machship Shipping ===
Contributors: jmartinezmachship, ktormes
Tags: machship, woomachship, woo_machship
Requires at least: 6.1
Tested up to: 6.5
Stable tag: 1.5.7
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Get Real-Time Pricing From 200+ Australian Freight Carriers In Your WooCommerce Checkout & Sync Orders Instantly With MachShip

== Description ==

Generate Real-Time Delivery Pricing In Your WooCommerce Checkout, And Automatically Sync Your WooCommerce Orders To Your MachShip account
* Connect to 200+ Leading Australian Carriers
* Add Live Carrier Pricing In Your Checkout
* Advanced boxing rules to mix and combine products into packages
* Add either percentage or fixed fee markups to your freight prices

This Plugin requires WooCommerce plugin and Machship Account.

[Machship](https://machship.com/product/what-is-machship) is a cloud-based freight management software that centrally manages your entire freight task, across all your carriers.

By connecting your WooCommerce store to your MachShip account you can charge your customer what you’re going to be charged and never lose on freight.

Our full integrated carrier list can be found [here.](https://machship.com/integrations/carrier-integration/partners)

This plugin is using the MachShip API and MachShip integration layer APIs.

Reference :
[Terms and Condition / Policy](https://machship.com/terms-and-policies/)

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Install Woocommerce in your wordpress
1. Install Machship Shipping plugin
1. Click to Woocommerce in the sidebar and select "Settings"
1. Open Shipping tab
1. Click a Zone and add Machship Shipping as one of your Shipping Method
1. Machship Shipping settings
    1. In the subtabs of Shipping, click "Machship Shipping" subtab.
    1. Tick Enable Machship Shipping
    1. Contact Machship for the Credentials and token needed for this plugin to operate
    1. Add Widget descriptions
    1. Add Shipping Margins
    1. Add Messages when No available Shipping
    1. Add Contact us information
    1. Add Warehouses locations
    1. Add Quick Quote label and position
    1. Then save
1. Migrate Product Box
    1. Click to Woocommerce in the sidebar and select "Machship Product Box Migration"
    1. Select package type
    1. Choose any warehouse you created earlier
    1. Then Click Go
1. Manually Edit Product
    1. Click Product in the sidebar
    1. Select which product to edit
    1. Scroll down and look for "Product Quote Settings Customisations"
    1. Tick Enable
    1. (Optional) Add international fields
    1. Select warehouse availability
    1. Add number of items/boxes for this product
    1. Then fill in the dimensions/weight of the item/box
    1. Select package type
    1. After all changes click Product page "Update"

== Frequently Asked Questions ==

= Do I require a Machship account to use this plugin? =

Yes - to use this plugin, you will need to have a Machship account with your carriers and rates loaded in.

= Can it show a single price, which is the cheapest of my carriers? =

Absolutely - we can group your carriers and return the cheapest price.

= Will shipping prices include residential fees and tailgate fees for heavy items? =

Yes - we can set a threshold for tailgate fees and either:
a) ask customers if they are a business/residential address
b) charge a residential surcharge to all customers

== Screenshots ==

1. Machship Shipping admin settings where you can configure the token, mode, warehouse, and carriers

== Changelog ==
= 1.5.7 =
* default box quantity to 1 for Dynamic mode

= 1.5.6 =
* fix for rounding string issue

= 1.5.5 =
* PHP version compatibility.
* update for deprecated script.
* fix for undefined variable.

= 1.5.4 =
* Fixes undefined variable on WooCommerce plugin version: 9.3.3


= 1.5.3 =
* full fixes for admin/public loading of code
* fix undefine data_items

= 1.5.2 =
* partial release

= 1.5.1 =
* revert admin loader
    * got issues on woo_machship_shipping_run parameter are null

= 1.5.0 =
* fix admin load

= 1.4.7 =
* Fix branch/tag

= 1.4.6 =
* remove unused property

= 1.4.5 =
* add validation for old user who doesn't have the fields yet

= 1.4.4 =
* fixes for importing multiple products using csv.

= 1.4.3 =
* apply_filters('machship_all_box_filter'): move the filter to a more suitable location

= 1.4.2 =
* cache key query for improved performance

= 1.4.1 =
* Fix Billing/Shipping address on checkout
    * upon tick on different shipping address, the request won't send the inputted postcode

= 1.4.0 =
* Added Product meta selection
    * select meta that will appended to live rate as product attributes

= 1.3.3 =
* Fix is_residential missing

= 1.3.2 =
* Fix groupings properly with multiple different warehouses
    * to show all quoted prices merge by same Grouping

= 1.3.1 =
* add fallback for warehouse id if nothing found

= 1.3.0 =
* Added new menu and page for import/export product box settings
* Optimize plugin and removed unnecessary libraries

= 1.2.29 =
* Official Release
* Clean up all warnings logs
* Fix dynamic liverate unnecessary quotes

== Upgrade Notice ==

= 1.3.1 =
Minor fix for quotes prices display.

= 1.3.0 =
Minor improvements and a new feature for import/export product box settings

= 1.2.29 =
This version fixed some bugs and several warnings in the code by improving logic validations
