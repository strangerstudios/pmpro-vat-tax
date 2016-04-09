=== Paid Memberships Pro - VAT Tax Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, tax, vat, eu
Requires at least: 3.5
Tested up to: 4.5
Stable tag: .2

Calculate VAT tax at checkout and allow customers with a VAT Number to avoid the tax.

== Description ==

This plugin adds a new section on the Membership Checkout form titled "European Union Residents VAT". The customer can select their EU country of residence from a drop-down box or enter their VAT number to avoid the tax. The entered VAT number is validated using the SOAP service provided through the European Commission (http://ec.europa.eu/taxation_customs/vies/technicalInformation.html).

VAT rates are automatically calculated based on the constant rates defined in the plugin. The rates in our plugin are those currently listed by the European Commission (http://ec.europa.eu/taxation_customs/index_en.htm)

== Installation ==

1. Make sure you have the Paid Memberships Pro plugin installed and activated.
1. Upload the `pmpro-vat-tax` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the `Plugins` menu in WordPress.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the GitHub issue tracker here: https://github.com/strangerstudios/pmpro-vat-tax/issues

For immediate help, also post to our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==
= .2 =
* BUG: Fixed bug where VAT was not applied if only a country of residence was chosen vs having the billing country set.
* ENHANCEMENT: Now setting the country of residence to the billing address if the country of residence is blank when the billing address is changed.

= .1.1 =
* BUG: Fixed warnings and issues when $bcountry was not available.

= .1 =
* Original version.
