== IDPay Gateway
Contributors: JMDMahdi, meysamrazmi, vispa
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

After installing and enabling this plugin, your customers can pay through IDPay gateway.
For doing a transaction through IDPay gateway, you must have an API Key. You can obtain the API Key by going to your [dashboard](https://idpay.ir/dashboard/web-services) in your IDPay [account](https://idpay.ir/user).

== Installation/Usage

after copying the plugin code into app directory, run the following commands in magento_root directory

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush

the you should be able to see IDPay payment method in:
Stores -> Configuration -> Sales -> Payment Methods -> Other Payment Methods -> IDPay

== Change log

- 08/09/06  V 1.0.0 Initial revision
