<?php

	namespace JetRails\Varnish\Observer\Save;

	use JetRails\Varnish\Model\Adminhtml\Config\Options\EnableDisable;
	use Magento\Framework\App\Cache\Type\Config as ConfigType;
	use Magento\Framework\App\Cache\TypeListInterface;
	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\Message\ManagerInterface;
	use Magento\PageCache\Model\Config as CacheConfig;
	use Magento\Store\Model\ScopeInterface;

	/**
	 * Config.php - This observer event is triggered whenever the store config is saved for this
	 * module.  It then validates all the fields and makes sure no invalid server information, urls,
	 * or routes are saved in the database.  If invalid ones are passed, then an error message is
	 * attached to the caller's session.
	 * @version         1.1.5
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Config implements ObserverInterface {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         ManagerInterface        _message        Instance of ManagerInterface
		 * @var         TypeListInterface       _cacheTypeList  Instance of the TypeListInterface
		 * @var         ScopeConfigInterface    _configReader   Instance of ScopeConfigInterface
		 * @var         WriterInterface         _configWriter   Instance of WriterInterface
		 */
		protected $_message;
		protected $_cacheTypeList;
		protected $_configReader;
		protected $_configWriter;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       ManagerInterface        message         Instance of ManagerInterface
		 * @param       TypeListInterface       cacheTypeList   Instance of the TypeListInterface
		 * @param       ScopeConfigInterface    configReader    Instance of ScopeConfigInterface
		 * @param       WriterInterface         configWriter    Instance of WriterInterface
		 */
		public function __construct (
			ManagerInterface $message,
			TypeListInterface $cacheTypeList,
			ScopeConfigInterface $configReader,
			WriterInterface $configWriter
		) {
			// Save the injected class instances
			$this->_message = $message;
			$this->_cacheTypeList = $cacheTypeList;
			$this->_configReader = $configReader;
			$this->_configWriter = $configWriter;
		}

		/**
		 * This method takes in a string of line separated varnish server values with host and port
		 * values separated with a colon.  It then loops through each one and it validates the
		 * values.  If all is valid the exact (trimmed) values will be saved in the store config.
		 * Otherwise an error message is added to the caller's session and the entry is ignored.
		 * @param       String              servers             Line separated server information
		 * @return      String                                  Only valid server entries
		 */
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

		/**
		 * This method takes in a line separated list of routes.  It then validates each one.  If
		 * they are valid then they are saved in the store config.  Otherwise an error message is
		 * attached to the caller's session.
		 * @param       String              routes              Unvalidated routes (line separated)
		 * @return      String                                  Valid route entries
		 */
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

		/**
		 * This method takes in a line separated list of URLs.  It then validates each one.  If they
		 * are valid then they are saved in the store config.  Otherwise an error message is
		 * attached to the caller's session.
		 * @param       String              URLs                Unvalidated URLs (line separated)
		 * @return      String                                  Valid URL entries
		 */
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

		/**
		 * This method is required because this class implements the ObserverInterface class.  This
		 * method gets executed when the registered event is fired for this class.  The event that
		 * this method will file for can be found in the events.xml file.
		 * @param       Observer            observer            Observer with event information
		 * @return      void
		 */
		public function execute ( Observer $observer ) {
			// Define the group path and the store scope
			$groupGC = "jetrails_varnish/general_configuration";
			$groupCEP = "jetrails_varnish/cache_exclusion_patterns";
			$storeScope = ScopeInterface::SCOPE_STORE;
			// Get the original values that were updated
			$servers = $this->_configReader->getValue ( "$groupGC/servers", $storeScope );
			$routes = $this->_configReader->getValue ( "$groupCEP/excluded_routes", $storeScope );
			$urls = $this->_configReader->getValue ( "$groupCEP/excluded_url_paths", $storeScope );
			// Define new values after validating
			$validatedServers = $this->_validateServers ( $servers );
			$validatedRoutes = $this->_validateRoutes ( $routes );
			$validatedUrls = $this->_validateUrls ( $urls );
			// Save the values back into database
			$this->_configWriter->save ( "$groupGC/servers", $validatedServers );
			$this->_configWriter->save ( "$groupCEP/excluded_routes", $validatedRoutes );
			$this->_configWriter->save ( "$groupCEP/excluded_url_paths", $validatedUrls );
			// Change caching type based on status
			$status = $this->_configReader->getValue ( "$groupGC/status", $storeScope );
			$this->_configWriter->save (
				"system/full_page_cache/caching_application",
				$status == EnableDisable::ENABLED ? CacheConfig::VARNISH : CacheConfig::BUILT_IN
			);
			// Clean the config cache so we get the right values when querying for them
			$this->_cacheTypeList->cleanType ( ConfigType::TYPE_IDENTIFIER );
		}

	}
