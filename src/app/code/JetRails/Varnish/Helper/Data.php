<?php

	namespace JetRails\Varnish\Helper;

	use JetRails\Varnish\Model\Adminhtml\Config\Options\EnableDisable;
	use JetRails\Varnish\Model\Adminhtml\Config\Options\YesNo;
	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\App\Helper\AbstractHelper;
	use Magento\Framework\App\ObjectManager;
	use Magento\PageCache\Model\Config as CacheConfig;
	use Magento\Store\Model\ScopeInterface;
	use Magento\Store\Model\StoreManagerInterface;

	/**
	 * Data.php - This helper class is responsible for data retrieval and configuration.  It can use
	 * the store config and the Magento session information to return data about the user and about
	 * the module configuration on the store scope.
	 * @version         1.1.3
	 * @package         JetRails® Varnish
	 * @category        Helper
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Data extends AbstractHelper {


		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         ScopeConfigInterface   _configReader   Instance of the ScopeConfigInterface
		 * @var         StoreManagerInterface  _configWriter   Instance of the StoreManagerInterface
		 * @var         WriterInterface        _storeManager   Instance of the WriterInterface
		 */
		protected $_configReader;
		protected $_configWriter;
		protected $_storeManager;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       ScopeConfigInterface   configReader    Instance of the ScopeConfigInterface
		 * @param       StoreManagerInterface  configWriter    Instance of the StoreManagerInterface
		 * @param       WriterInterface        storeManager    Instance of the WriterInterface
		 */
		public function __construct (
			ScopeConfigInterface $configReader,
			StoreManagerInterface $storeManager,
			WriterInterface $configWriter
		) {
			// Save the injected class instances
			$this->_configReader = $configReader;
			$this->_storeManager = $storeManager;
			$this->_configWriter = $configWriter;
		}

		/**
		 * This method first cleans the store config cache in order to retrieve a non cached store
		 * config value.  It then retrieves it based on the passed scope and returns it to the user.
		 * By default the store config scope is the store scope.
		 * @param       String              path                The path of the store config data
		 * @param       ScopeInterface      scope               What scope from the store config
		 * @return      String                                  The value of the variable
		 */
		private function _getStoreValue ( $path, $scope = ScopeInterface::SCOPE_STORE ) {
			// Ask the config reader to get the value in the store scope
			return $this->_configReader->getValue ( $path, $scope );
		}

		/**
		 * This method inspects the user agent and determines what operating system and browser they
		 * are using.  We gather this information to display it in the log file.  This will help put
		 * blame in the correct direction.
		 * @return      Object                                  OS and browser of client in object
		 */
		private function _getClientBrowser () {
			// Initialize the user agent and set the default values
			$agent = $_SERVER ["HTTP_USER_AGENT"];
			$type = "Unknown";
			$os = "Unknown";
			// Extract the operating system
			if ( preg_match ( "/linux/i", $agent ) ) $os = "Linux";
			if ( preg_match ( "/macintosh|mac os x/i", $agent ) ) $os = "Mac";
			if ( preg_match ( "/windows|win32/i", $agent ) ) $os = "Windows";
			// Extract the browser type
			if ( preg_match ( "/msie/i", $agent ) && !preg_match ( "/opera/i", $agent ) ) {
				$type = "Internet Explorer";
			}
			elseif ( preg_match ( "/firefox/i", $agent ) ) $type = "Firefox";
			elseif ( preg_match ( "/chrome/i", $agent ) ) $type = "Chrome";
			elseif ( preg_match ( "/safari/i", $agent ) ) $type = "Safari";
			elseif ( preg_match ( "/opera/i", $agent ) ) $type = "Opera";
			elseif ( preg_match ( "/netscape/i", $agent ) ) $type = "Netscape";
			// Return the browser and os
			return ( object ) [ "os" => $os, "browser" => $type ];
		}

		/**
		 * This method gets the currently logged in user information.  It is used when an admin is
		 * logged into the backend and tries to purge or white list urls from varnish cache.  When
		 * these requests come through from the controllers, then we get their user information in
		 * order to propagate blame more accurately in the log files.
		 * @return      Object                                  User information in object
		 */
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
				$session = "Magento\Backend\Model\Auth\Session";
				$session = $objectManager->create ( $session );
				$remoteAddress = "Magento\Framework\HTTP\PhpEnvironment\RemoteAddress";
				$remoteAddress = $objectManager->get ( $remoteAddress );
				// Return the (admin) user's session information along with browser info
				$user = $session->getUser ();
				$clientBrowser = $this->_getClientBrowser ();
				return ( object ) [
					"interface" => "backend",
					"id" => $user->getId (),
					"username" => $user->getUserName (),
					"email" => $user->getEmail (),
					"ip" => $remoteAddress->getRemoteAddress (),
					"browser" => $clientBrowser->browser,
					"system" => $clientBrowser->os
				];
			}
		}

		/**
		 * This method simply tells the user if the caching application is set to varnish cache or
		 * built-in cache.  The return value is a boolean and if varnish cache is set then we return
		 * true, otherwise false.  This value can be found in the store config page under system >
		 * caching application.
		 * @return      Boolean                                 Is Varnish Cache set as caching app?
		 */
		public function isEnabled () {
			// Get the value of the caching application and save it
			$enabled = $this->_getStoreValue ( "system/full_page_cache/caching_application" );
			// Return true if it is set to varnish
			return $enabled == CacheConfig::VARNISH;
		}

		/**
		 * This method simply takes in a boolean value that represents the enable/disable state of this
		 * module. It then saves it into the configuration. Note that config cache is not invalidated in
		 * this method.
		 * @param       boolean             status              What to set the enable setting to
		 * @return      void
		 */
		public function setEnable ( $status ) {
			// Translate the status into a status state
			$value = $status ? EnableDisable::ENABLED : EnableDisable::DISABLED;
			// Simply save it into the store config
			$this->_configWriter->save ( "jetrails_varnish/general_configuration/status", $value );
		}

		/**
		 * This method takes in a class constant from CacheConfig and sets it in the store config.
		 * @param       CacheConfig         value               Class constant for caching app state
		 * @return      void
		 */
		public function setCachingApplication ( $value ) {
			// Simply save it into the store config
			$this->_configWriter->save ( "system/full_page_cache/caching_application", $value );
		}

		/**
		 * This method tells the caller if debug mode is enabled in the store config.
		 * @return      Boolean                                 Is debug mode enabled?
		 */
		public function isDebugMode () {
			// Get the value of the debug mode from the store
			$debug = $this->_getStoreValue ("jetrails_varnish/general_configuration/debug");
			// Return true debug mode is true
			return $debug == EnableDisable::ENABLED;
		}

		/**
		 * This method tells the caller if we enabled the feature where we purge the product URL
		 * after making changed to the product (saving).
		 * @return      Boolean                                 Purge after product save?
		 */
		public function shouldPurgeAfterProductSave () {
			// Get the value from the store configuration
			$value = $this->_getStoreValue ("jetrails_varnish/automatic_cache_purge/product_save");
			// Return true if it is on
			return $value == YesNo::YES;
		}

		/**
		 * This method tells the caller if we enabled the feature where we purge the CMS page URL
		 * after making changes to the CMS page (saving).
		 * @return      Boolean                                 Purge after CMS page save?
		 */
		public function shouldPurgeAfterCmsPageSave () {
			// Get the value from the store configuration
			$value = $this->_getStoreValue ("jetrails_varnish/automatic_cache_purge/cms_page_save");
			// Return true if it is on
			return $value == YesNo::YES;
		}

		/**
		 * This method returns an array of store views.  Each array element contains an object that
		 * describes the store view.  The information that is contained is the store view id, store
		 * view name, and store view base URL.
		 * @return      Array                                   Store view information objects
		 */
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

		/**
		 * This method gets the current values for the varnish servers and returns an array of
		 * formatted and validated objects that contain the varnish server's host and port values.
		 * @return      Array                                   Varnish servers with host and port
		 */
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

		/**
		 * This method returns an array of validated urls that we define in the store config section
		 * of the web store.
		 * @return      Array                               List of excluded URLs
		 */
		public function getExcludedUrls () {
			// Get the values from the store configuration
			$path = "jetrails_varnish/cache_exclusion_patterns/excluded_url_paths";
			$urls = explode ( "\n", $this->_getStoreValue ( $path ) );
			// Filter out all the empty lines
			return array_filter ( $urls, function ( $i ) { return $i != ""; });
		}

		/**
		 * This method returns an array of validated routes that we define in the store config
		 * section of the web store.
		 * @return      Array                               List of excluded routes
		 */
		public function getExcludedRoutes () {
			// Get the values from the store configuration
			$path = "jetrails_varnish/cache_exclusion_patterns/excluded_routes";
			$routes = explode ( "\n", $this->_getStoreValue ( $path ) );
			// Filter out all the empty lines
			return array_filter ( $routes, function ( $i ) { return $i != ""; });
		}

	}
