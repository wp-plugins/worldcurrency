<?php
/*
Plugin Name: WorldCurrency
Plugin URI: http://www.cometicucinoilweb.it/blog/en/worldcurrency-plugin-for-wordpress/
Description: Recognises users by IP address and shows them converted values in their local currency, you can write post/pages in multiple currencies.
Version: 1.19
Date: 12 Jamuary 2015
Author: Daniele Tieghi
Author URI: www.cometicucinoilweb.it/blog/en/who-we-are/daniele-tieghi/

   Copyright 2012 - 2012 Daniele Tieghi  (email: daniele.tieghi(at)gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Built on ideas and small code portions from LocalCurrency (http://www.jobsinchina.com/resources/wordpress-plugin-localcurrency)
Uses IP2C (http://firestats.cc/wiki/ip2c) to determine user's country
Uses Yahoo! Finance (http://finance.yahoo.com) for conversion rates
Updates since version 1.16 by Jon Scaife (https://jonscaife.com, https://diymediahome.org)
*/
	// Loads currency infos
		require_once 'currencies.inc.php';

	// Retrieve current saved options from Wordpress
		$dt_wc_options = get_option('dt_wc_options');

	// Register HOOKS
		register_activation_hook(__FILE__, 'dt_wc_createOptions');
		add_action('admin_menu', 'dt_wc_adminPage');
		add_action('wp_head', 'dt_wc_head');
		add_action('publish_page', 'dt_wc_publish');
		add_action('publish_post', 'dt_wc_publish');
		add_shortcode('worldcurrency', 'dt_wc_shortcode');
		add_shortcode('worldcurrencybox', 'dt_wc_shortcode_box');
		add_filter('the_content', 'dt_wc_content', $dt_wc_options['plugin_priority']);

		add_action( 'wp_ajax_nopriv_worldcurrency', 'dt_wc_ajaxGetExchangeRate' );
		add_action( 'wp_ajax_worldcurrency', 'dt_wc_ajaxGetExchangeRate' );
		add_action( 'wp_ajax_nopriv_worldcurrencybox', 'dt_wc_ajaxGetCurrencySelectionBox' );
		add_action( 'wp_ajax_worldcurrencybox', 'dt_wc_ajaxGetCurrencySelectionBox' );

	// Register Widget
		require_once 'worldcurrency.widget.php';

	// Create options on Activation
		function dt_wc_createOptions($force = false) {

			$dt_wc_options = get_option('$dt_wc_options');

			if ($force || !isset($dt_wc_options['plugin_link']))			$dt_wc_options['plugin_link'] = 'true';
			if ($force || !isset($dt_wc_options['yahoo_link']))				$dt_wc_options['yahoo_link'] = 'true';
			if ($force || !isset($dt_wc_options['cache_rates']))			$dt_wc_options['cache_rates'] = null;
			if ($force || !isset($dt_wc_options['cache_time']))				$dt_wc_options['cache_time'] = 0;
			if ($force || !isset($dt_wc_options['historic_rates']))			$dt_wc_options['historic_rates'] = 'false';
			if ($force || !isset($dt_wc_options['hide_if_same']))			$dt_wc_options['hide_if_same'] = 'true';
			if ($force || !isset($dt_wc_options['output_format']))			$dt_wc_options['output_format'] = ' (%to_symbol%%to_value%)';
			if ($force || !isset($dt_wc_options['thousands_separator']))	$dt_wc_options['thousands_separator'] = ',';
			if ($force || !isset($dt_wc_options['decimal_separator']))		$dt_wc_options['decimal_separator'] = '.';
			if ($force || !isset($dt_wc_options['bottom_select']))			$dt_wc_options['bottom_select'] = 'true';
			if ($force || !isset($dt_wc_options['include_jquery']))			$dt_wc_options['include_jquery'] = 'true';
			if ($force || !isset($dt_wc_options['include_always']))			$dt_wc_options['include_always'] = 'false';
			if ($force || !isset($dt_wc_options['jquery_no_conflict']))		$dt_wc_options['jquery_no_conflict'] = 'true';
			if ($force || !isset($dt_wc_options['ajax_over_ssl']))			$dt_wc_options['ajax_over_ssl'] = 'false';
			if ($force || !isset($dt_wc_options['plugin_priority']))		$dt_wc_options['plugin_priority'] = 10;
			if ($force || !isset($dt_wc_options['additional_css']))			$dt_wc_options['additional_css'] = <<<EOT
.worldcurrency {
	color: #888;
}
.worldcurrency_selection_box {
	margin: 10px 0px 10px 0px;
	border: 1px dashed #CCC;
	padding: 5px 5px 3px 5px;
	background-color: #F2F2F2;
	line-height: 18px;
}
.worldcurrency_selection_box .credits {
	font-size: 11px;
}
EOT;

			update_option('dt_wc_options', $dt_wc_options);
		}

	// Create the options page
		function dt_wc_adminPage() {
			require_once('worldcurrency-admin.php');
		}

	// Add page scripts
		function dt_wc_head() {
			global $post;

			$dt_wc_options = get_option('dt_wc_options');

			$jQuerySymbol = $dt_wc_options['jquery_no_conflict'] == 'true' ? 'jQuery' : '$';

			// Include the script only if necessary
			if ($dt_wc_options['include_always'] == 'true' || get_post_meta($post->ID, 'wc_force', true) == 1 || strpos($post->post_content, 'worldcurrency') !== false) {

				$usercurrency = dt_wc_userlocation();
				if (!$usercurrency) {$usercurrency = 'EUR';}

				echo "\n<!-- DT WorldCurrency Code -->\n";

				// Include jQuery if needed
				if ($dt_wc_options['include_jquery'] == 'true')
					echo "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'></script>\n";

				if ($dt_wc_options['ajax_over_ssl'] == 'true') {
					$ajax_url = wp_nonce_url(str_replace('http:','https:', admin_url('admin-ajax.php')), 'worldcurrency_safe');
				} else {
					$ajax_url = wp_nonce_url(str_replace('https:','http:', admin_url('admin-ajax.php')), 'worldcurrency_safe');
				}

				?>
				<script type="text/javascript">
				<!--

					function worldCurInit() {

					<?php if ($dt_wc_options['jquery_no_conflict'] == 'true'): ?>
						jQuery.noConflict();
					<?php endif; ?>

					dt_worldCurrency_update = function(userCurrency) {
						// For each worldcurrency <span> get the value
						<?php echo $jQuerySymbol; ?>('.worldcurrency').each(function() {

							var theSpan = <?php echo $jQuerySymbol; ?>(this);
							<?php echo $jQuerySymbol; ?>.ajax({
								url: '<?php echo $ajax_url; ?>',
								dataType: 'html',
								type: 'GET',
								data: {'action':'worldcurrency', 'to':theSpan.attr('target') ? theSpan.attr('target') : userCurrency, 'from':theSpan.attr('curr'), 'value':theSpan.attr('value'), 'postId':theSpan.attr('postId'), 'historic':theSpan.attr('historic') ? theSpan.attr('historic') : '<?php echo $dt_wc_options['historic_rates']; ?>'},
								success: function(html, textStatus) {
									theSpan.html(html);
								},
								error: function(jqXHR, textStatus, errorThrown) {
									alert(textStatus + ': ', + errorThrown);
								}
							});
						});

						// For each Currency selection box, chose the right value
						<?php echo $jQuerySymbol; ?>('.worldcurrency_select').val(userCurrency);

					}

					// When the page is loaded
					<?php echo $jQuerySymbol; ?>(document).ready(function() {

						// Register already rendere currency selection boxes
						<?php echo $jQuerySymbol; ?>('.worldcurrency_select').change(function() {
							dt_worldCurrency_update(<?php echo $jQuerySymbol; ?>(this).attr('value'));
						});

						// Render Currency selection boxes
						<?php echo $jQuerySymbol; ?>('.worldcurrency_selection_box_placeholder').each(function() {

							var theDiv = <?php echo $jQuerySymbol; ?>(this);
							<?php echo $jQuerySymbol; ?>.ajax({
								url: '<?php echo $ajax_url; ?>',
								dataType: 'html',
								type: 'GET',
								data: {'action':'worldcurrencybox'},
								success: function(html, textStatus) {
									theDiv.html(html);

									theSelect = theDiv.children('.worldcurrency_selection_box').children('.worldcurrency_select');
									// Set the current currency
									theSelect.val('<?php echo $usercurrency; ?>');
									// On change update currency
									theSelect.change(function() {
										dt_worldCurrency_update(<?php echo $jQuerySymbol; ?>(this).attr('value'));
									});
								},
								error: function(jqXHR, textStatus, errorThrown) {
									alert(textStatus + ': ', + errorThrown);
								}
							});
						});

						dt_worldCurrency_update('<?php echo $usercurrency; ?>');

					});

					}

					function runWCWhenJQueryIsLoaded() {
					    if (window.jQuery){
					    	worldCurInit();
					    } else {
					        setTimeout(runWCWhenJQueryIsLoaded, 5);
					    }
					}

					runWCWhenJQueryIsLoaded();

				-->
				</script>
				<style type="text/css">
				<?php echo $dt_wc_options['additional_css']; ?>
				</style>
				<!-- End of WorldCurrency DT Extension Code -->
				<?php

			}
		}

	// Retrieve user location via IP2C
		function dt_wc_userlocation() {
			global $dt_wc_locationlist;
			require_once dirname(__FILE__).'/ip2c/ip2c.php';

			$ip2c = new ip2country();
			$res = $ip2c->get_country($_SERVER['REMOTE_ADDR']);

			if ($res == false) {
				return false;
			} else {
				return $dt_wc_locationlist[$res['id2']];
			}
		}

	/**
	 * Handler for [worldcurrency] shortcode
	 *
	 * USAGE:
	 *
	 * 		[worldcurrency cur="EUR" value="25"]
	 * 			in united states will show:
	 * 			30 USD
	 *
	 * Parameters:
	 *
	 * 		curr="***" 				-> the name of the value currency
	 * 		target="***" 			-> the name of the target currency (if you want to force it)
	 * 		value="***"				-> the value used for exchange
	 * 		historic="true|false"	-> (optional) override main plugin setting
	 *
	 * @param array $attr
	 */
	function dt_wc_shortcode($attr) {
		global $post;

		if (!isset($attr['curr']) || !isset($attr['value']))
			return '[worldcurrency error: curr="" and value="" parameters needed]';

		$out = '<span class="worldcurrency" postId="'.$post->ID.'" curr="'.$attr['curr'].'" value="'.$attr['value'].'" ';

		if (isset($attr['historic']))	$out .= 'historic="'.$attr['historic'].'" ';
		if (isset($attr['target']))		$out .= 'target="'.$attr['target'].'" ';

		$out .= '></span>';

		return $out;
	}

	/**
	 * Handler for [worldcurrencybox] shortcode that shows the currency selection box
	 */
	function dt_wc_shortcode_box() {
		return dt_wc_getCurrencySelectionBox();
	}

	/**
	 * On publishing/updating post/page saves the current currency conversion rates for future historic uses
	 */
	function dt_wc_publish() {
		global $post;

		// Check if there are rates attached to the post or else saves them
		if (!($serializedQuotes = get_post_meta($post->ID, 'wc_rates', true))) {

			// Include our Yahoo!Finance class
			require_once 'yahoofinance.class.php';
			$YahooFinance = new yahoofinance();

			update_post_meta($post->ID, 'wc_rates', $serializedQuotes = $YahooFinance->getSerializedQuotes());
			update_post_meta($post->ID, 'wc_rates_date', time());

		}
	}

	/**
	 * Adds a currecy selection box at the end of the page/post if needed
	 * @param string $theContent
	 */
	function dt_wc_content($theContent) {
		global $dt_wc_options;
		if ($dt_wc_options['bottom_select'] == 'true' && strpos($theContent, 'worldcurrency') !== false)
			$theContent .= dt_wc_getCurrencySelectionBox();
		return $theContent;
	}


	/**
	 * Generate and returns the HTML for the currency selection box
	 */
	function dt_wc_getCurrencySelectionBox() {
		$out = '';

		// Include our Yahoo!Finance class
			require_once 'currencies.inc.php';
			global $dt_wc_currencylist;

		// Retrieve current WC saved options from Wordpress
			$dt_wc_options = get_option('dt_wc_options');

		$out .= '<div class="worldcurrency_selection_box">';

			// Renders the select box
				$out .= '<div style="float:left;margin-right:4px;">Show currencies in:</div>';
				$out .= '<select class="worldcurrency_select">';
				foreach ($dt_wc_currencylist as $currencyCode => $currencyInfo) {
					if (in_array($currencyCode, array('---'))) continue;
					$out .= '<option value="'.$currencyCode.'">'.$currencyInfo['name'].'</option>';
				}
				$out .= '</select>';

			// Renders the credits
				if ($dt_wc_options['plugin_link'] == 'true' || $dt_wc_options['yahoo_link'] == 'true') $out .= '<div class="credits">Powered by';
				if ($dt_wc_options['plugin_link'] == 'true') $out .= ' the <a href="http://www.cometicucinoilweb.it/blog/en/worldcurrency-plugin-for-wordpress/" target="_blank" title="World Currency plugin for Wordpress">WordCurrency</a> plugin.';
				if ($dt_wc_options['yahoo_link'] == 'true') $out .= ' <a href="http://finance.yahoo.com" title="Visit Yahoo! Finance" target="_blank">Yahoo! Finance</a> for the rates.';
				if ($dt_wc_options['plugin_link'] == 'true' || $dt_wc_options['yahoo_link'] == 'true') $out .= '</div>';

		$out .= '</div>'."\n";

		return $out;
	}

	function dt_wc_ajaxGetCurrencySelectionBox() {
		// Make sure there's nothing bad in the URL
			foreach ($_GET as $key => $value)
				$_GET[$key] = htmlentities(stripslashes($value));

		// Don't proceed if we don't have enough info or if the nonce fails
			if (!check_admin_referer('worldcurrency_safe'))
				die();

		echo dt_wc_getCurrencySelectionBox();

		exit;
	}

	function dt_wc_ajaxGetExchangeRate() {

		// Make sure there's nothing bad in the URL
			foreach ($_GET as $key => $value)
				$_GET[$key] = htmlentities(stripslashes($value));

		// Don't proceed if we don't have enough info or if the nonce fails
			if (!isset($_GET['value']) || !isset($_GET['historic']) || !isset($_GET['from']) || !isset($_GET['to']) || !check_admin_referer('worldcurrency_safe'))
				exit;

		// Include our Yahoo!Finance class
			require_once 'yahoofinance.class.php';
			global $dt_wc_currencylist;
			$YahooFinance = new yahoofinance();

		// Retrieve current WC saved options from Wordpress
			$dt_wc_options = get_option('dt_wc_options');

		// Check if we need only historic rates
		if ($_GET['historic'] == 'true' && isset($_GET['postId'])) {

			// Check if there are rates attached to the post or else saves them
			if (!($serializedQuotes = get_post_meta($_GET['postId'], 'wc_rates', true))) {
				update_post_meta($_GET['postId'], 'wc_rates', $serializedQuotes = $YahooFinance->getSerializedQuotes());
				update_post_meta($_GET['postId'], 'wc_rates_date', time());
			}

		} else {

			// We need current rates, check if we have them stored in options
			if (!!$dt_wc_options['cache_rates'] && $dt_wc_options['cache_time'] >= (time() - 86400)) {
				// Fetch Rates from options if they are fresh
				$serializedQuotes = $dt_wc_options['cache_rates'];
			} else {
				// Or get them from Yahoo!Finance and store them
				$dt_wc_options['cache_rates'] = $serializedQuotes = $YahooFinance->getSerializedQuotes();
				$dt_wc_options['cache_time'] = time();
				update_option('dt_wc_options', $dt_wc_options);
			}

		}

		// Get the separators format
		$thousands_separator = isset($dt_wc_options['thousands_separator']) ? $dt_wc_options['thousands_separator'] : '.';
		$decimals_separator = isset($dt_wc_options['decimal_separator']) ? $dt_wc_options['decimal_separator'] : ',';


		// Load the quotes obtained
			$YahooFinance->loadSerializedQuotes($serializedQuotes);

		// Fetch the desired exchange rate and prepare all the parameters

			$exchange_rate	= $YahooFinance->getExchangeRate($_GET['from'], $_GET['to']);

			$from_code		= $_GET['from'];
			$from_value		= $_GET['value'];
			$from_name		= $dt_wc_currencylist[$from_code]['name'];
			$from_symbol	= $dt_wc_currencylist[$from_code]['symbol'];

			$to_code		= $_GET['to'];
			$to_value		= $from_value * $exchange_rate;
			$to_name		= $dt_wc_currencylist[$to_code]['name'];
			$to_symbol		= $dt_wc_currencylist[$to_code]['symbol'];

		// Round the numbers
			$exchange_rate = number_format($exchange_rate,2,',','.');
			$from_value = $from_value > 100 ? number_format($from_value,0,$decimals_separator,$thousands_separator) : number_format($from_value, 2,$decimals_separator,$thousands_separator);
			$to_value = $to_value > 100 ? number_format($to_value,0,$decimals_separator,$thousands_separator) : number_format($to_value, 2,$decimals_separator,$thousands_separator);

		// Do not show conversions to the same currency
			if ($dt_wc_options['hide_if_same'] == 'true' && $from_code == $to_code)
				exit;

		// Echo in the required format
			echo str_replace(array('%exchange_rate%','%from_code%','%from_value%','%from_name%','%from_symbol%','%to_code%','%to_value%','%to_name%','%to_symbol%'), array($exchange_rate,$from_code,$from_value,$from_name,$from_symbol,$to_code,$to_value,$to_name,$to_symbol), $dt_wc_options['output_format']);

		exit;
	}
