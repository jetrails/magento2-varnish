<?php

	namespace JetRails\Varnish\Helper;

	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\Helper\AbstractHelper;
	use Magento\Framework\App\ObjectManager;
	use Magento\Store\Model\StoreManagerInterface;

	class Purger extends AbstractHelper {

		protected $_data;
		protected $_storeManager;

		public function __construct ( Data $data, StoreManagerInterface $storeManager ) {
			$this->_data = $data;
			$this->_storeManager = $storeManager;
		}

		private function _purge ( $url, $additionalHeaders = [] ) {
			// Initialize responses
			$responses = [];
			// Get all the configured servers and traverse them all
			foreach ( $this->_data->getVarnishServersWithPorts () as $varnishServer ) {
				// Initialize a curl object
				$handle = curl_init ( $varnishServer->host . $url->path );
				// Set curl options
				curl_setopt ( $handle, CURLOPT_PORT, $varnishServer->port );
				curl_setopt ( $handle, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, true );
				curl_setopt ( $handle, CURLOPT_AUTOREFERER, true );
				curl_setopt ( $handle, CURLOPT_HEADER, true );
				curl_setopt ( $handle, CURLOPT_CONNECTTIMEOUT, 120 );
				curl_setopt ( $handle, CURLOPT_TIMEOUT, 120 );
				curl_setopt ( $handle, CURLOPT_MAXREDIRS, 10 );
				curl_setopt ( $handle, CURLOPT_CUSTOMREQUEST, "PURGE" );
				curl_setopt ( $handle, CURLOPT_HTTPHEADER, $additionalHeaders );
				// Execute curl request and save response code
				$response = curl_exec ( $handle );
				$responseCode = curl_getinfo ( $handle, CURLINFO_HTTP_CODE );
				// Close curl request using handle and return response code
				curl_close ( $handle );
				// Append response to response url
				array_push ( $responses, ( object ) [
					"server" 	=> $varnishServer->host . ":" . $varnishServer->port,
					"target" 	=> $url->host . $url->path,
					"status" 	=> $responseCode
				]);
			}
			// Return all the responses
			return $responses;
		}

		public function validateUrl ( $url ) {
			// Prepare url for validation
			$url = trim ( $url );
			$regexp = "/^(https?:\/{2})?([^\/\.]+\.[^\/]{2,})(?:(\/.*$))?/i";
			// Check to see if the url is valid
			if ( preg_match ( $regexp, $url, $matches ) ) {
				// If the trailing slash is missing from domain name, add it
				if ( count ( $matches ) == 3 ) $matches [ 3 ] = "/";
				// Return the extracted pieces of the url
				return ( object ) [
					"host" => $matches [ 2 ],
					"path" => $matches [ 3 ]
				];
			}
			// If it is invalid return false
			return "The passed url is invalid";
		}

		public function validateAndResolveStoreId ( $id ) {
			// Load all the store views
			$stores = $this->_storeManager->getStores ();
			// Check to see if the id exists
			if ( array_key_exists ( intval ( $id ), $stores ) ) {
				// Get the store's base url
				$store = $stores [ intval ( $id ) ];
				$storeBaseUrl = $store->getBaseUrl ();
				// Extract the host and path
				$regexp = "/^(https?:\/{2})?([^\/\.]+\.[^\/]{2,})(?:(\/.*$))?/i";
				if ( preg_match ( $regexp, $storeBaseUrl, $matches ) ) {
					// If the trailing slash is missing from domain name, add it
					if ( count ( $matches ) == 3 ) $matches [ 3 ] = "/";
					// Return the extracted pieces of the url
					return ( object ) [
						"host" => $matches [ 2 ],
						"path" => $matches [ 3 ]
					];
				}
				// If the regexp did not match then there is a malformed url saved
				return "Invalid base url saved for store view";
			}
			// If it is invalid return false
			return "Invalid store id passed";
		}

		public function purgeStore ( $url ) {
			// Set the additional headers for this request
			$typeParam = "JetRails-Purge-Type: store";
			$hostParam = "JetRails-Host: $url->host";
			$urlParam = "JetRails-Url: $url->path";
			$additionalHeaders = [ $typeParam, $hostParam, $urlParam ];
			// Attempt to purge and return response headers
			return $this->_purge ( $url, $additionalHeaders );
		}

		public function purgeUrl ( $url ) {
			// Set the additional headers for this request
			$typeParam = "JetRails-Purge-Type: url";
			$hostParam = "JetRails-Host: $url->host";
			$urlParam = "JetRails-Url: $url->path";
			$additionalHeaders = [ $typeParam, $hostParam, $urlParam ];
			// Attempt to purge and return response headers
			return $this->_purge ( $url, $additionalHeaders );
		}

		public function purgeAll () {
			// Make a url object and define the additional headers for this request
			$url = ( object ) [ "host" => null, "path" => "/" ];
			$additionalHeaders = [ "JetRails-Purge-Type: all" ];
			// Attempt to purge and return response headers
			return $this->_purge ( $url, $additionalHeaders );
		}

	}