<?php

	namespace JetRails\Varnish\Helper;

	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\Helper\AbstractHelper;
	use Magento\Store\Model\StoreManagerInterface;
	use Magento\UrlRewrite\Model\UrlFinderInterface;
	use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

	/**
	 * Purger.php - This class is encapsulated with purge specific methods in order to use them on
	 * the backend controllers and the console commands.  These methods specifically purge single
	 * URLs, store views, and even purge all cache from a list of given varnish servers.
	 * @version         1.1.9
	 * @package         JetRails® Varnish
	 * @category        Helper
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Purger extends AbstractHelper {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         Data                  _data              Instance of the Data helper class
		 * @var         StoreManagerInterface _storeManager      Instance of the StoreManager
		 * @var         UrlFinderInterface    _urlFinder         Instance of the UrlFinder
		 */
		protected $_data;
		protected $_storeManager;
		protected $_urlFinder;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @var         Data                data                Instance of the Data helper class
		 * @var         Data                storeManager        Instance of the StoreManager
		 *
		 */
		public function __construct (
			Data $data,
			StoreManagerInterface $storeManager,
			UrlFinderInterface $urlFinder
		) {
			// Save the injected class instances
			$this->_data = $data;
			$this->_storeManager = $storeManager;
			$this->_urlFinder = $urlFinder;
		}

		/**
		 * This is a helper method that helps resolve url rewrites. It looks for all url rewrites with a
		 * given target path. It then uses this target path to find all request paths that lead to said
		 * target path. This search is done recursively, so if the rewrite is not direct and instead there
		 * are many rewrites that lead to the target path, we will find them all. This method is also
		 * acyclic therefore if there is a cycle in the rewrite logic, we won't fall for it.
		 * @param       string              targetPath          Target path to look for
		 * @param       array               visited             Already seen target paths
		 * @return      array                                   Request paths that lead to target path
		 */
		public function getUrlRewrites ( $targetPath, $visited = [] ) {
			// Base case, if already seen then return
			if ( in_array ( $targetPath, $visited ) ) return [];
			array_push ( $visited, $targetPath );
			// Find all rewrites with target path and recursively find the derivatives
			$rewrites = $this->_urlFinder->findAllByData ( [ UrlRewrite::TARGET_PATH => $targetPath ] );
			$rewrites = array_map ( function ( $i ) { return $i->getRequestPath (); }, $rewrites );
			$results = array_merge ( [ $targetPath ], $rewrites );
			foreach ( $rewrites as $rewrite ) {
				$results = array_merge ( $results, $this->getUrlRewrites ( $rewrite, $visited ) );
			}
			// Return a unique set of rewrites
			return array_unique ( $results );
		}

		/**
		 * This method is private and it is used to traverse through all the configured varnish
		 * servers.  It then constructs the request and adds the additional header parameters into
		 * the request packet.  It sends this packet to all the configured varnish servers and
		 * requests a purge of cache.  It records all the varnish server's responses and returns
		 * them to the caller.
		 * @param       Object              url                 Host / path URL definition
		 * @param       Array               additionalHeaders   Optional additional packet params
		 * @return      Array                                   All varnish server responses
		 */
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
					"server"    => $varnishServer->host . ":" . $varnishServer->port,
					"target"    => $url->host . $url->path,
					"status"    => $responseCode
				]);
			}
			// Return all the responses
			return $responses;
		}

		/**
		 * This method takes in a URL and validates it.  Once we determine it is valid, we return an
		 * object that consists of the URL's hostname and path.  If there was an error with the URL,
		 * then an error message is passed back
		 * @param       String              url                 The url to validate and examine
		 * @return      Object|String                           Object on success, else error string
		 */
		public function validateUrl ( $url ) {
			// Prepare url for validation
			$url = trim ( $url );
			$regexp = "/^(https?:\/{2})?([^\/\.]+\.[^\/]{2,})(?:(\/.*$))?/i";
			// Check to see if the url is valid
			if ( preg_match ( $regexp, $url, $matches ) ) {
				// If the trailing slash is missing from domain name, add it
				if ( count ( $matches ) == 3 ) $matches [ 3 ] = "/";
				// Return the extracted pieces of the url
				return ( object ) [ "host" => $matches [ 2 ], "path" => $matches [ 3 ] ];
			}
			// If it is invalid return false
			return "The passed url is invalid";
		}

		/**
		 * This method takes in a store view id and it makes sure that that store view id exists
		 * within the store.  If there is an error then a string is returned.  Otherwise, an object
		 * is returned that specifies the base URL of the store view and it is broken down into host
		 * and path definitions.
		 * @param       Integer             id                  The store view id to examine
		 * @return      Object|String                           Object on success, else error string
		 */
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

		/**
		 * This method takes in an object that defines a URL's host and path as data members and it
		 * uses those values to construct a packet for the varnish servers.  This packet will purge
		 * all urls that are part of this store view.
		 * @param       Object              url                 Host / path object for store view
		 * @return      Array                                   Responses from varnish servers
		 */
		public function purgeStore ( $url ) {
			// Set the additional headers for this request
			$typeParam = "JetRails-Purge-Type: store";
			$hostParam = "JetRails-Host: $url->host";
			$urlParam = "JetRails-Url: $url->path";
			$additionalHeaders = [ $typeParam, $hostParam, $urlParam ];
			// Attempt to purge and return response headers
			return $this->_purge ( $url, $additionalHeaders );
		}

		/**
		 * This method takes in an object that defines a URL's host and path as data members and it
		 * uses those values to construct a packet for the varnish servers.  This packet will purge
		 * all urls that match the passed url exactly.
		 * @param       Object              url                 Host / path object for URL
		 * @return      Array                                   Responses from varnish servers
		 */
		public function purgeUrl ( $url ) {
			// Set the additional headers for this request
			$typeParam = "JetRails-Purge-Type: url";
			$hostParam = "JetRails-Host: $url->host";
			$urlParam = "JetRails-Url: $url->path";
			$additionalHeaders = [ $typeParam, $hostParam, $urlParam ];
			// Attempt to purge and return response headers
			return $this->_purge ( $url, $additionalHeaders );
		}

		/**
		 * This method takes in an object that defines a URL's host and path as data members and it
		 * uses those values to construct a packet for the varnish servers.  This packet will purge
		 * all urls that are stored in that varnish server.
		 * @return      Array                                   Responses from varnish servers
		 */
		public function purgeAll () {
			// Make a url object and define the additional headers for this request
			$url = ( object ) [ "host" => null, "path" => "/" ];
			$additionalHeaders = [ "JetRails-Purge-Type: all" ];
			// Attempt to purge and return response headers
			return $this->_purge ( $url, $additionalHeaders );
		}

	}
