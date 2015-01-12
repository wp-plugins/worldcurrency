<?php

	require_once 'currencies.inc.php';

	/**
	 * Class used to get informations from yahoo finance or to load them from a serialized string
	 * Used by WordCurrency plugin for Wordpress
	 *
	 * @author Daniele Tieghi
	 */
	class yahoofinance {

		/**
		 * Array with informations about all the currencies
		 * @var array
		 * @example $currencyList['EUR'] = array('name'=>'Euro (EUR)', 'symbol'=>'&#8364;');
		 */
		private $currencyList = array();

		/**
		 * Array with quote couple names (ie: USDEUR, USDTHB)
		 * @var array
		 */
		private $quoteCouples = array();

		/**
		 * All the quotes received from Yahoo!Finance (or loaded from saved data)
		 * @var array
		 */
		private $quotes = array();


		//////////////////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////////////////

		/**
		 * Build basic data
		 */
		public function __construct() {
			global $dt_wc_currencylist;
			$this->currencyList = $dt_wc_currencylist;
			$this->buildUSDQuoteCouples();
		}

		/**
		 * Build all the quote couples (with USD as a base) that we need to get from Yahoo!Finance
		 */
		private function buildUSDQuoteCouples() {
			$this->quoteCouples = array();
			foreach ($this->currencyList as $currencyCode => $currencyInfo) {
				if (in_array($currencyCode, array('USD','---')))
					continue;
				$this->quoteCouples[] = 'USD'.$currencyCode;
			}
			return $this->quoteCouples;
		}

		/**
		 * Calls the Yahoo!Finance service and gets the quotes for every quote couple we need
		 */
		private function getCurrentQuotesFromYahooFinance() {

			// Build the query parameters
			$parameters = array(
				's='.implode('=X+', $this->quoteCouples).'=X',	// The quotes we need (USDEUR=X+USDTHB=X+...)
				'f=sl1',										// The output we want (http://www.gummy-stuff.org/Yahoo-data.htm)
				'e=.csv',										// The format we want (not needed)
			);

			// Use a Socket connection to call Yahoo!Finance service
			$host="download.finance.yahoo.com";
			$fp = @fsockopen($host, 80, $errno, $errstr, 30);

			// Check if the socket is working
			if (!$fp) {
				return array();
			} else {

				// Send the request
					$out = "GET /d/quotes.csv?".implode('&', $parameters)." HTTP/1.0\r\n";
				    $out .= "Host: download.finance.yahoo.com\r\n";
					$out .= "Connection: Close\r\n\r\n";
					@fputs($fp, $out);

				// Read the answer
					$data = '';
					while (!@feof($fp)) {
						$data .= @fgets($fp, 128);
					}
					@fclose($fp);

				// Parse the answer
					$quotes = array();
					preg_match_all('/"(?P<quote>[A-Z]{6})=X",(?P<value>[0-9.]+)/', $data, $result, PREG_SET_ORDER);
					for ($matchi = 0; $matchi < count($result); $matchi++) {
						$quotes[$result[$matchi]['quote']] = $result[$matchi]['value'];
					}
					$quotes['USDUSD'] = 1.0;	// Add conversion from USD to USD

				$this->quotes = $quotes;
				return $quotes;
			}
		}

		/**
		 * Checks if there are quotes present
		 */
		private function getQuotesIfNeeded() {
			if (count($this->quotes) == 0)
				$this->getCurrentQuotesFromYahooFinance();
		}

		/**
		 * Returns a serialized version of the quotes for storing
		 * @return string
		 */
		public function getSerializedQuotes() {
			$this->getQuotesIfNeeded();
			return serialize($this->quotes);
		}

		/**
		 * Load some stored serialized quotes
		 * @param string $serializedQuotes
		 */
		public function loadSerializedQuotes($serializedQuotes) {
			$this->quotes = unserialize($serializedQuotes);
		}

		public function getExchangeRate($from, $to) {
			$this->getQuotesIfNeeded();

			// if currencies or rates are missing return 0
				if (!isset($this->quotes['USD'.$from]) || $this->quotes['USD'.$from] == 0 || !isset($this->quotes['USD'.$to]) || $this->quotes['USD'.$to] == 0)
					return 0;

			return $this->quotes['USD'.$to] / $this->quotes['USD'.$from];
		}

	}


