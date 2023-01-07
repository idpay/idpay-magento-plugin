=== IDPay Gateway for Magento 2 ===
Title : IDPay Gateway for Magento 2
Stable tag: 2.0.0
Tested up to: 2.4.5
Contributors: MimDeveloper.Tv (Mohammad-Malek), Mohammad Nabipour
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

After installing and enabling this plugin, your customers can pay through IDPay gateway.
For doing a transaction through IDPay gateway,
you must have an API Key.
You can obtain the API Key by going to your [dashboard](https://idpay.ir/dashboard/web-services) in your IDPay [account](https://idpay.ir/user).

== Installation/Usage

after copying the plugin code into (app/code) directory, run the following commands in magento_root directory

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush

the you should be able to see IDPay payment method in:
Stores -> Configuration -> Sales -> Payment Methods -> Other Payment Methods -> IDPay

* If you need to use this plugin in Test mode, check the "Sandbox".

== Changelog ==

== 2.0.0, Nov 13, 2022 ==
* Tested Up With Magento 2
* Check Double Spending Correct
* Check Does Not Xss Attack Correct
* Check Save Transaction & Statuses Correct
* Replacement Deprecated Function in Magento 2
* Compatible With Magento 2
* First Official Release

== 1.0.0, Nov 13, 2015 ==
* Develope Release
