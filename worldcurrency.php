<?php
/*
Plugin Name: WorldCurrency
Plugin URI: http://www.cometicucinoilweb.it/blog/en/worldcurrency-plugin-for-wordpress/
Description: Recognises users by IP address and shows them converted values in their local currency, you can write post/pages in multiple currencies.
Version: 1.6
Date: 21th February 2012
Author: Daniele Tieghi
Author URI: http://www.cometicucinoilweb.it/blog/chi-siamo/daniele-tieghi/
   
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
		
	// Register Widget
		require_once 'worldcurrency.widget.php';

	// Create options on Activation
		function dt_wc_createOptions($force = false) {
			
			$dt_wc_options = get_option('$dt_wc_options');
	
			if ($force || !isset($dt_wc_options['plugin_link']))		$dt_wc_options['plugin_link'] = 'true';
			if ($force || !isset($dt_wc_options['yahoo_link']))			$dt_wc_options['yahoo_link'] = 'true';
			if ($force || !isset($dt_wc_options['cache_rates']))		$dt_wc_options['cache_rates'] = null;
			if ($force || !isset($dt_wc_options['cache_time']))			$dt_wc_options['cache_time'] = 0;
			if ($force || !isset($dt_wc_options['historic_rates']))		$dt_wc_options['historic_rates'] = 'false';
			if ($force || !isset($dt_wc_options['hide_if_same']))		$dt_wc_options['hide_if_same'] = 'true';
			if ($force || !isset($dt_wc_options['output_format']))		$dt_wc_options['output_format'] = '(~%to_value%%to_symbol% %to_code%)';
			if ($force || !isset($dt_wc_options['bottom_select']))		$dt_wc_options['bottom_select'] = 'true';
			if ($force || !isset($dt_wc_options['include_jquery']))		$dt_wc_options['include_jquery'] = 'true';
			if ($force || !isset($dt_wc_options['jquery_no_conflict']))		$dt_wc_options['jquery_no_conflict'] = 'false';
			if ($force || !isset($dt_wc_options['plugin_priority']))	$dt_wc_options['plugin_priority'] = 10;
			if ($force || !isset($dt_wc_options['additional_css']))		$dt_wc_options['additional_css'] = <<<EOT
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
			if (strpos($post->post_content, 'worldcurrency') !== false) {
				
				$usercurrency = dt_wc_userlocation();
				if (!$usercurrency) {$usercurrency = 'EUR';}
				
				echo "\n<!-- DT WorldCurrency Code -->\n";
				
				// Include jQuery if needed
				if ($dt_wc_options['include_jquery'] == 'true')
					echo "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'></script>\n";
					
				?>
				<script type="text/javascript">
				<!--

					<?php if ($dt_wc_options['jquery_no_conflict'] == 'true'): ?>
						jQuery.noConflict();
					<?php endif; ?>
	
					dt_worldCurrency_update = function(userCurrency) {
						// For each worldcurrency <span> get the value
						<?php echo $jQuerySymbol; ?>('.worldcurrency').each(function() {
		
							var theSpan = <?php echo $jQuerySymbol; ?>(this);
							<?php echo $jQuerySymbol; ?>.ajax({
								url: '<?php echo wp_nonce_url(plugins_url(dirname(plugin_basename(__FILE__))).'/_getexchangerate.php', 'worldcurrency_safe'); ?>',
								dataType: 'html',
								type: 'GET',
								data: {'to':userCurrency, 'from':theSpan.attr('curr'), 'value':theSpan.attr('value'), 'postId':theSpan.attr('postId'), 'historic':theSpan.attr('historic') ? theSpan.attr('historic') : '<?php echo $dt_wc_options['historic_rates']; ?>'},
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
								url: '<?php echo wp_nonce_url(plugins_url(dirname(plugin_basename(__FILE__))).'/_getcurrencyselectionbox.php', 'worldcurrency_safe'); ?>',
								dataType: 'html',
								type: 'GET',
								data: {},
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
				
				-->
				</script>
				<style type="text/css">
				<?php echo $dt_wc_options['additional_css']; ?>
				</style>
				<!-- End of LocalCurrency DT Extension Code -->
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
	 * 		value="***"				-> the value used for exchange
	 * 		historic="true|false"	-> (optional) override main plugin setting
	 * 
	 * @param array $attr
	 */
	function dt_wc_shortcode($attr) {
		global $post;
		
		if (!isset($attr['curr']) || !isset($attr['value']))
			return '[worldcurrency error: curr="" and value="" parameters needed]';
		
		if (isset($attr['historic']))
			return '<span class="worldcurrency" postId="'.$post->ID.'" curr="'.$attr['curr'].'" value="'.$attr['value'].'" historic="'.$attr['historic'].'"></span>';
		else
			return '<span class="worldcurrency" postId="'.$post->ID.'" curr="'.$attr['curr'].'" value="'.$attr['value'].'"></span>';
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