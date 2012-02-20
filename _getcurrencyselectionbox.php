<?php
	
	// Load Wordpress so we have access to the functions we need
		require_once('../../../wp-config.php');
		require_once(ABSPATH . 'wp-settings.php');
		
	// Make sure there's nothing bad in the URL
		foreach ($_GET as $key => $value) 
			$_GET[$key] = htmlentities(stripslashes($value));	
	
	// Don't proceed if we don't have enough info or if the nonce fails
		if (!check_admin_referer('worldcurrency_safe')) 
			die();
		
	// Include our Yahoo!Finance class
		require_once 'currencies.inc.php';
		global $dt_wc_currencylist;
	
	// Retrieve current WC saved options from Wordpress
		$dt_wc_options = get_option('dt_wc_options');

	echo '<div class="worldcurrency_selection_box">';
	
		// Renders the select box
			echo _e('Show currencies in:').' <select class="worldcurrency_select">'."\n";
			foreach ($dt_wc_currencylist as $currencyCode => $currencyInfo) {
				if (in_array($currencyCode, array('---'))) continue;
				echo '<option value="'.$currencyCode.'">'.$currencyInfo['name'].'</option>'."\n";
			}
			echo '</select><br/>'."\n";
			
		// Renders the credits
			if ($dt_wc_options['plugin_link'] || $dt_wc_options['yahoo_link']) echo '<small>Powered by';
			if ($dt_wc_options['plugin_link']) echo ' the <a href="http://www.cometicucinoilweb.it/blog/en/worldcurrency-plugin-for-wordpress/" target="_blank" title="World Currency plugin for Wordpress">WordCurrency</a> plugin.';
			if ($dt_wc_options['yahoo_link']) echo ' <a href="http://finance.yahoo.com" title="Visit Yahoo! Finance" target="_blank">Yahoo! Finance</a> for the rates.';
			if ($dt_wc_options['plugin_link'] || $dt_wc_options['yahoo_link']) echo '</small>';
		
	echo '</div>';