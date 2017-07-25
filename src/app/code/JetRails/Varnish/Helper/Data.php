<?php

	namespace JetRails\Varnish\Helper;

	use JetRails\Varnish\Model\Adminhtml\Config\Options\EnableDisable;
	use JetRails\Varnish\Model\Adminhtml\Config\Options\YesNo;
	use Magento\Framework\App\Cache\TypeListInterface;
	use Magento\Framework\App\Cache\Type\Config;
	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\App\Helper\AbstractHelper;
	use Magento\Framework\App\ObjectManager;
	use Magento\PageCache\Model\Config as CacheConfig;
	use Magento\Store\Model\ScopeInterface;
	use Magento\Store\Model\StoreManagerInterface;

	class Data extends AbstractHelper {

		protected $_cacheTypeList;
		protected $_configReader;
		protected $_configWriter;
		protected $_storeManager;

		public function __construct (
			TypeListInterface $cacheTypeList,
			ScopeConfigInterface $configReader,
			StoreManagerInterface $storeManager,
			WriterInterface $configWriter
		) {
			$this->_cacheTypeList = $cacheTypeList;
			$this->_configReader = $configReader;
			$this->_storeManager = $storeManager;
			$this->_configWriter = $configWriter;
		}

		private function _getStoreValue ( $path, $scope = ScopeInterface::SCOPE_STORE ) {
			// Clean the config cache so we go the right values
			$this->_cacheTypeList->cleanType ( Config::TYPE_IDENTIFIER );
			// Ask the config reader to get the value in the store scope
			return $this->_configReader->getValue ( $path, $scope );
		}

		private function _getClientBrowser () {
			// Initialize the user agent and set the default values
			$agent = $_SERVER ["HTTP_USER_AGENT"];
			$type = "Unknown";
			$os = "Unknown";
			// Extract the operating system
			if (preg_match ( "/linux/i", $agent ) ) $os = "Linux";
			if ( preg_match ( "/macintosh|mac os x/i", $agent ) ) $os = "Mac";
			if ( preg_match ( "/windows|win32/i", $agent ) ) $os = "Windows";
			// Extract the browser type
			if ( preg_match ( "/msie/i", $agent ) && !preg_match ( "/opera/i", $agent ) ) $type = "Internet Explorer";
			elseif ( preg_match ( "/firefox/i", $agent ) ) $type = "Firefox";
			elseif ( preg_match ( "/chrome/i", $agent ) ) $type = "Chrome";
			elseif ( preg_match ( "/safari/i", $agent ) ) $type = "Safari";
			elseif ( preg_match ( "/opera/i", $agent ) ) $type = "Opera";
			elseif ( preg_match ( "/netscape/i", $agent ) ) $type = "Netscape";
			// Return the browser and os
			return ( object ) [ "os" => $os, "browser" => $type ];
		}

		public function getLoggedInUserInfo () {
			// Check to see if caller is using CLI
			if ( php_sapi_name () === "cli" ) {
				// If it is then gather some information and return it
				return ( object ) [ "interface" => "console", "username" => get_current_user () ];
			}
			// Otherwise this is a request that has a session attached to it
			else {
				// We use object manager here because with DI in CLI there is no session
				$objectManager = ObjectManager::getInstance ();
				$session = $objectManager->create ("Magento\Backend\Model\Auth\Session");   
				$removeAddress = $objectManager->get ("Magento\Framework\HTTP\PhpEnvironment\RemoteAddress");
				// Return the (admin) user's session information along with browser info
				$user = $session->getUser ();
				$clientBrowser = $this->_getClientBrowser ();
				return ( object ) [
					"interface" => "backend",
					"id" => $user->getId (),
					"username" => $user->getUserName (),
					"email" => $user->getEmail (),
					"ip" => $removeAddress->getRemoteAddress (),
					"browser" => $clientBrowser->browser,
					"system" => $clientBrowser->os
				];
			}
		}

		public function isEnabled () {
			// Get the value of the caching application and save it
			$enabled = $this->_getStoreValue ( "system/full_page_cache/caching_application" );
			// Return true if it is set to varnish
			return $enabled == CacheConfig::VARNISH;
		}

		public function setCachingApplication ( $value ) {
			// Simply save it into the store config
			$this->_configWriter->save ( "system/full_page_cache/caching_application", $value );
		}

		public function isDebugMode () {
			// Get the value of the debug mode from the store
			$debug = $this->_getStoreValue ("jetrails_varnish/general_configuration/debug");
			// Return true debug mode is true
			return $debug == EnableDisable::ENABLED;
		}

		public function shouldPurgeAfterProductSave () {
			// Get the value from the store configuration
			$value = $this->_getStoreValue ("jetrails_varnish/automatic_cache_purge/product_save");
			// Return true if it is on
			return $value == YesNo::YES;
		}

		public function shouldPurgeAfterCmsPageSave () {
			// Get the value from the store configuration
			$value = $this->_getStoreValue ("jetrails_varnish/automatic_cache_purge/cms_page_save");
			// Return true if it is on
			return $value == YesNo::YES;
		}

		public function getStoreViews () {
			// Return a list of stores from the store manager
			return array_map ( function ( $store ) {
				return ( object ) [
					"id" => $store->getStoreId (),
					"name" => $store->getName (),
					"url" => $store->getBaseUrl ()
				];
			}, array_values ( $this->_storeManager->getStores () ) );
		}

		public function getBackendServer () {
			// Get the value from magento
			$path = "jetrails_varnish/general_configuration/backend";
			$backend = $this->_getStoreValue ( $path );
			// If the backend field is empty then return false
			if ( trim ( $backend ) == "" ) return false;
			// Explode the backend value and separate host from port
			$backend = explode ( ":", $backend );
			// Return as an object
			return ( object ) [
				"host" => $backend [ 0 ],
				"port" => intval ( $backend [ 1 ] )
			];
		}

		public function getVarnishServersWithPorts () {
			// Get the values from the store configuration
			$path = "jetrails_varnish/general_configuration/servers";
			$lines = explode ( "\n", $this->_getStoreValue ( $path ) );
			// Remove all the empty lines
			$lines = array_filter ( $lines, function ( $i ) { return $i != ""; });
			// Only get unique items only
			$lines = array_unique ( $lines );
			// Split the server and the port
			return array_map ( function ( $line ) {
				// Separate the host from the port and return as object
				$line = explode ( ":", $line );
				return ( object ) [
					"host" => $line [ 0 ],
					"port" => intval ( $line [ 1 ] )
				];
			}, $lines );
		}

		public function getExcludedUrls () {
			// Get the values from the store configuration
			$path = "jetrails_varnish/cache_exclusion_patterns/excluded_url_paths";
			$urls = explode ( "\n", $this->_getStoreValue ( $path ) );
			// Filter out all the empty lines
			return array_filter ( $urls, function ( $i ) { return $i != ""; });
		}

		public function getExcludedRoutes () {
			// Get the values from the store configuration
			$path = "jetrails_varnish/cache_exclusion_patterns/excluded_routes";
			$routes = explode ( "\n", $this->_getStoreValue ( $path ) );
			// Filter out all the empty lines
			return array_filter ( $routes, function ( $i ) { return $i != ""; });
		}

	}