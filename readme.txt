=== WorldCurrency ===
Contributors: WhiteCubes
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6CUNDYFWV4NUW
Tags: currency, exchange rates, currency converter, currency rates, travel, financial, eCommerce
Requires at least: 2.8.0
Tested up to: 3.3.1
Stable tag: 1.4

Recognises users by IP address and shows them converted values in their local currency, you can write post/pages in multiple currencies.

== Description ==
Show currency values to readers in their local currency, you can use multipe currencies per post.
[worldcurrency curr="THB" value="120"] will became (~3.5$ USD) in United States and (~3€ EUR) from Europe)

Any other informations may be found on the [plugin's homepage](http://www.cometicucinoilweb.it/blog/en/worldcurrency-plugin-for-wordpress/)

= Why Use It? =
It is really usefull (expecially for travel and commerce blogs) to show prices in the currency of the reader, so they'll find themself at home ;)
With this plugin you can obtain somethig like this: The price of the green curry was of 120 bath (~3€ EUR)

= Features: =
* Determines the reader's country via IP address, using [IP2C](http://firestats.cc/wiki/ip2c)
* Obtains exchange rates from [Yahoo! Finance](http://finance.yahoo.com/)
* Uses AJAX so that converting currency values doesn't delay page load times
* Caches exchange rates locally to minimize calls to Yahoo! Finance
* Only does something if there is a currency value in the post
* Allows to specify different currencies per post/page
* It is possible to totally personalize the output format, for example: EUR 2 | 2€ | (~2 EUR)
* Gives site owner the choice of using current or historic (at publishing time) rates (globally or also per single conversion/post).
* Allows visitors to change their currency via a selection box
* Currency selection box widget available
* Currency selection box shorttag available [worldcurrencybox]
* The currency selection box may be putted everywhere via html placeholder
* Is possible to choose to hide conversion if target and origin currency are the same

= How To Use (once plugin is installed) =
Enter any currency values you want converted with the [worldcurrency] shorttag. 
	  
	[worldcurrency cur="EUR" value="25"]
		in united states will show: 
			(~30$ USD)
  
Parameters:
  
  		curr="***" 				-> the name of the value currency
  		value="***"				-> the value used for exchange
  		historic="true|false"	-> (optional) override main plugin setting

If you want to show the currency selection box anywhere in the post/page use:

	[worldcurrencybox]
	  
= Compatibility: =
* This plugin is written and tested on Wordpress 3.2.1 and Wordpress 3.3.1, but I think it will work on many other versions.

= Support: =
This plugin is not officially supported because is made in my free time, but leave a comment on the [plugin's homepage](http://www.cometicucinoilweb.it/blog/en/worldcurrency-plugin-for-wordpress/) and I may help ;)


= Disclaimer =
This plugin is released under the [GPL licence](http://www.gnu.org/copyleft/gpl.html). I do not accept any responsibility for any damages or losses, direct or indirect, that may arise from using the plugin or these instructions. This software is provided as is, with absolutely no warranty. Please refer to the full version of the GPL license for more information.

== Installation ==
1. Download the plugin file and unzip it.
2. Upload the `worldcurrency` folder to the `wp-content/plugins/` folder.
3. Activate the WorldCurrency plugin within WordPress.

Alternatively, you can install the plugin automatically through the WordPress Admin interface by going to Plugins -> Add New and searching for WorldCurrency.

== Upgrade Notice ==
1. Download the plugin file and unzip it.
2. Upload the `worldcurrency` folder to the `wp-content/plugins/` folder, overwriting the existing files.
3. Go to the plugin settings and Update or Reset them to be sure that new settings are saved.

Alternatively, you can update this plugin through the WordPress Admin interface.

== Frequently Asked Questions ==

= Will the plugin have extra features in the future =
Extra features may be implemented if I find them usefull/interesting and if I have time to develop them.
Feel free to propose them.

== Screenshots ==
There are no screenshots for this plugin, but there is a full demo on the [Plugin's Homepage](http://www.cometicucinoilweb.it/blog/en/worldcurrency-plugin-for-wordpress/)

== Changelog ==

= 1.0 (19th February 2012) =
* Initial Release

= 1.1 (20th February 2012) =
* Minor improvements and Currency selection box shorttag

= 1.2 (20th February 2012) =
* Minor corrections
* Possibility to choose to hide conversion if target and origin currency are the same

= 1.4 (20th February 2012) =
* Estetic improvements

== Credits ==
* Built on ideas and small code portions from [LocalCurrency by Stephen Cronin](http://www.jobsinchina.com/resources/wordpress-plugin-localcurrency)
* This plugin uses [IP2C](http://firestats.cc/wiki/ip2c) to determine which country the visitor is from.
* This plugin uses [Yahoo! Finance](http://finance.yahoo.com/) to determine the exchange rates.