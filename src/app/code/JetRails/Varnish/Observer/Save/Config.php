<?php

	namespace JetRails\Varnish\Observer\Save;

	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\Message\ManagerInterface;
	use Magento\Store\Model\ScopeInterface;

	class Config implements ObserverInterface {

		protected $_message;
		protected $_configReader;
		protected $_configWriter;

		public function __construct (
			ManagerInterface $message,
			ScopeConfigInterface $configReader,
			WriterInterface $configWriter
		) {
			$this->_message = $message;
			$this->_configReader = $configReader;
			$this->_configWriter = $configWriter;
		}

		private function _validateServers ( $servers ) {
			// Clean the value first
			$servers = explode ( "\n", trim ( $servers ) );
			$servers = array_filter ( $servers, function ( $i ) { return trim ( $i ) != ""; } );
			// Loop through each entry
			$servers = array_map ( function ( $server ) {
				// Trim the line
				$server = trim ( $server );
				// Check to make sure it has a colon
				if ( preg_match ( "/^(.+):([1-9][0-9]+)$/i", $server, $matches ) ) {
					// Extract the host and the port
					$host = $matches [ 1 ];
					$port = intval ( $matches [ 2 ] );
					// Set validation flags for validation
					$validPort = $port > 0 && $port <= 65535;
            		$validIp = filter_var ( $host, FILTER_VALIDATE_IP );
            		$validDomain1 = strpos ( $host, "/" ) === false;
            		$validDomain2 = filter_var ( "http://" . $host, FILTER_VALIDATE_URL );
            		$validDomain = $validDomain1 && $validDomain2;
            		// If the entry is valid, then return the value
            		if ( $validPort && ( $validIp || $validDomain ) ) return trim ( $server );
				}
				// Add a warning and return nothing
				return $this->_message->addWarning (
					"Ignoring invalid varnish server: <font color='#EB5202' ><b>$server</b></font>"
				) ? "" : "";
			}, $servers );
			// Remove all the empty rows
			$servers = array_filter ( $servers, function ( $i ) { return trim ( $i ) != ""; } );
			// Combine back into single string and return the new value
			return implode ( "\n", $servers );
		}

		private function _validateBackend ( $backend ) {
			// Clean the backend string
			$backend = trim ( $backend );
			// Check to make sure it has a colon
			if ( preg_match ( "/^(.+):([1-9][0-9]+)$/i", $backend, $matches ) ) {
				// Extract the host and the port
				$host = $matches [ 1 ];
				$port = intval ( $matches [ 2 ] );
				// Set validation flags for validation
				$validPort = $port > 0 && $port <= 65535;
        		$validIp = filter_var ( $host, FILTER_VALIDATE_IP );
        		$validDomain1 = strpos ( $host, "/" ) === false;
        		$validDomain2 = filter_var ( "http://" . $host, FILTER_VALIDATE_URL );
        		$validDomain = $validDomain1 && $validDomain2;
        		// If the entry is valid, then return the value
        		if ( $validPort && ( $validIp || $validDomain ) ) return $backend;
			}
			// If it is empty then just accept it
			else if ( $backend == "" ) return;
			// Add a warning and return nothing
			return $this->_message->addWarning (
				"Ignoring invalid backend server: <font color='#EB5202' ><b>$backend</b></font>"
			) ? "" : "";
		}

		private function _validateRoutes ( $routes ) {
			// Clean the value first
			$routes = explode ( "\n", trim ( $routes ) );
			$routes = array_filter ( $routes, function ( $i ) { return trim ( $i ) != ""; } );
			// Loop through each entry
			$routes = array_map ( function ( $route ) {
				// Trim the line
				$route = trim ( $route );
				// Make sure the entry is valid, if it is return it
				$regexp = "/^[a-z0-9_-]{3,}(?:\/[a-z0-9_-]{3,}){0,3}$/i";
				if ( preg_match ( $regexp, $route ) ) return $route;
				// Add a warning and return nothing
				return $this->_message->addWarning (
					"Ignoring invalid exclusion route: <font color='#EB5202' ><b>$route</b></font>"
				) ? "" : "";
			}, $routes );
			// Remove all the empty rows
			$routes = array_filter ( $routes, function ( $i ) { return trim ( $i ) != ""; } );
			// Combine back into single string and return the new value
			return implode ( "\n", $routes );
		}

		private function _validateUrls ( $urls ) {
			// Clean the value first
			$urls = explode ( "\n", trim ( $urls ) );
			$urls = array_filter ( $urls, function ( $i ) { return trim ( $i ) != ""; } );
			// Loop through each entry
			$urls = array_map ( function ( $url ) {
				// Trim the line
				$url = trim ( $url );
				if ( $url [ 0 ] == "/" ) return "/" . trim ( $url, "/" );
				// Add a warning and return nothing
				return $this->_message->addWarning (
					"Ignoring invalid exclusion url: <font color='#EB5202' ><b>$url</b></font>"
				) ? "" : "";
			}, $urls );
			// Remove all the empty rows
			$urls = array_filter ( $urls, function ( $i ) { return trim ( $i ) != ""; } );
			// Combine back into single string and return the new value
			return implode ( "\n", $urls );
		}

		public function execute ( Observer $observer ) {
			// Define the group path and the store scope
			$groupGC = "jetrails_varnish/general_configuration";
			$groupCEP = "jetrails_varnish/cache_exclusion_patterns";
			$storeScope = ScopeInterface::SCOPE_STORE;
			// Get the original values that were updated
			$servers = $this->_configReader->getValue ( "$groupGC/servers", $storeScope );
			$backend = $this->_configReader->getValue ( "$groupGC/backend", $storeScope );
			$routes = $this->_configReader->getValue ( "$groupCEP/excluded_routes", $storeScope );
			$urls = $this->_configReader->getValue ( "$groupCEP/excluded_url_paths", $storeScope );
			// Define new values after validating
			$validatedServers = $this->_validateServers ( $servers );
			$validatedBackend = $this->_validateBackend ( $backend );
			$validatedRoutes = $this->_validateRoutes ( $routes );
			$validatedUrls = $this->_validateUrls ( $urls );
			// Save the values back into database
			$this->_configWriter->save ( "$groupGC/servers", $validatedServers );
			$this->_configWriter->save ( "$groupGC/backend", $validatedBackend );
			$this->_configWriter->save ( "$groupCEP/excluded_routes", $validatedRoutes );
			$this->_configWriter->save ( "$groupCEP/excluded_url_paths", $validatedUrls );
		}

	}