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
		
	echo dt_wc_getCurrencySelectionBox();