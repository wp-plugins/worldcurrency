<?php
	
	// Load Wordpress so we have access to the functions we need
		require_once('../../../wp-config.php');
		require_once(ABSPATH . 'wp-settings.php');
		
	// Make sure there's nothing bad in the URL
		foreach ($_GET as $key => $value) 
			$_GET[$key] = htmlentities(stripslashes($value));	
	
	// Don't proceed if we don't have enough info or if the nonce fails
		if (!isset($_GET['value']) || !isset($_GET['postId']) || !isset($_GET['historic']) || !isset($_GET['from']) || !isset($_GET['to']) || !check_admin_referer('worldcurrency_safe')) 
			die();
		
	// Include our Yahoo!Finance class
		require_once 'yahoofinance.class.php';
		global $dt_wc_currencylist;
		$YahooFinance = new yahoofinance();
	
	// Retrieve current WC saved options from Wordpress
		$dt_wc_options = get_option('dt_wc_options');
		
	// Check if we need only historic rates 
	if ($_GET['historic'] == 'true') {
		
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
		$from_value = $from_value > 100 ? number_format($from_value,0,',','.') : number_format($from_value, 2,',','.');
		$to_value = $to_value > 100 ? number_format($to_value,0,',','.') : number_format($to_value, 2,',','.');
	
	// Do not show conversions to the same currency
		if ($dt_wc_options['hide_if_same'] == 'true' && $from_code == $to_code)
			return;
		
	// Echo in the required format
		echo str_replace(array('%exchange_rate%','%from_code%','%from_value%','%from_name%','%from_symbol%','%to_code%','%to_value%','%to_name%','%to_symbol%'), array($exchange_rate,$from_code,$from_value,$from_name,$from_symbol,$to_code,$to_value,$to_name,$to_symbol), $dt_wc_options['output_format']);
		
		