<?php

	namespace JetRails\Varnish\Helper;

	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\App\Helper\AbstractHelper;
	use Magento\Framework\App\ObjectManager;
	use Magento\Framework\App\Request\Http;
	use Magento\PageCache\Model\Config as CacheConfig;
	use Magento\Store\Model\ScopeInterface;
	use Magento\Store\Model\StoreManagerInterface;
	use Magento\PageCache\Model\Cache\Server;
	use Magento\Framework\Module\ModuleListInterface;

	/**
	 * @version         2.0.2
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Data extends AbstractHelper {

		protected $_configReader;
		protected $_configWriter;
		protected $_storeManager;
		protected $_http;
		protected $_server;
		protected $_modules;

		const DEBUG_DISABLED = 1;
		const DEBUG_ENABLED = 2;

		public function __construct (
			ScopeConfigInterface $configReader,
			StoreManagerInterface $storeManager,
			WriterInterface $configWriter,
			Http $http,
			Server $server,
			ModuleListInterface $modules
		) {
			$this->_configReader = $configReader;
			$this->_storeManager = $storeManager;
			$this->_configWriter = $configWriter;
			$this->_http = $http;
			$this->_server = $server;
			$this->_modules = $modules;
		}

		protected function _getConfigValue ( $path, $scope = ScopeInterface::SCOPE_STORE, $storeId = null ) {
			if ( $scope === null ) {
				$scope = ScopeInterface::SCOPE_STORE;
			}
			return $this->_configReader->getValue ( $path, $scope, $storeId );
		}

		protected function _getClientBrowser () {
			$agent = isset ( $_SERVER ["HTTP_USER_AGENT"] ) ? $_SERVER ["HTTP_USER_AGENT"] : "";
			$type = "Unknown";
			$os = "Unknown";
			if ( preg_match ( "/linux/i", $agent ) ) $os = "Linux";
			if ( preg_match ( "/macintosh|mac os x/i", $agent ) ) $os = "Mac";
			if ( preg_match ( "/windows|win32/i", $agent ) ) $os = "Windows";
			if ( preg_match ( "/msie/i", $agent ) && !preg_match ( "/opera/i", $agent ) ) {
				$type = "Internet Explorer";
			}
			elseif ( preg_match ( "/firefox/i", $agent ) ) $type = "Firefox";
			elseif ( preg_match ( "/chrome/i", $agent ) ) $type = "Chrome";
			elseif ( preg_match ( "/safari/i", $agent ) ) $type = "Safari";
			elseif ( preg_match ( "/opera/i", $agent ) ) $type = "Opera";
			elseif ( preg_match ( "/netscape/i", $agent ) ) $type = "Netscape";
			return ( object ) [ "os" => $os, "browser" => $type ];
		}

		public function getLoggedInUserInfo () {
			$objectManager = ObjectManager::getInstance ();
			if ( php_sapi_name () === "cli" ) {
				return ( object ) [ "interface" => "console", "username" => get_current_user () ];
			}
			else if ( $this->_http->getHeader ("Authorization") ) {
				$remoteAddress = "Magento\Framework\HTTP\PhpEnvironment\RemoteAddress";
				$remoteAddress = $objectManager->get ( $remoteAddress );
				return ( object ) [
					"interface" => "api",
					"ip" => $remoteAddress->getRemoteAddress ()
				];
			}
			else {
				$session = "Magento\Backend\Model\Auth\Session";
				$session = $objectManager->create ( $session );
				$remoteAddress = "Magento\Framework\HTTP\PhpEnvironment\RemoteAddress";
				$remoteAddress = $objectManager->get ( $remoteAddress );
				$user = $session->getUser ();
				$clientBrowser = $this->_getClientBrowser ();
				return ( object ) [
					"interface" => "backend",
					"id" => $user ? $user->getId () : "n/a",
					"username" => $user ? $user->getUserName () : "n/a",
					"email" => $user ? $user->getEmail () : "n/a",
					"ip" => $remoteAddress->getRemoteAddress (),
					"browser" => $clientBrowser->browser,
					"system" => $clientBrowser->os
				];
			}
		}

		public function isEnabled () {
			$enabled = $this->_getConfigValue ( "system/full_page_cache/caching_application" );
			return $enabled == CacheConfig::VARNISH;
		}

		public function isDebugMode () {
			$debug = $this->_getConfigValue ("jetrails_varnish/general_configuration/debug");
			return $debug == self::DEBUG_ENABLED;
		}

		public function getModuleVersion () {
			return $this->_modules->getOne ("JetRails_Varnish") ["setup_version"];
		}

		public function getVarnishServersWithPorts () {
			$servers = [];
			foreach ( $this->_server->getUris () as $entry ) {
				$servers [] = (object) [
					"host" => $entry->getHost (),
					"port" => $entry->getPort ()
				];
			}
			return $servers;
		}

		public function getVarnishServerConfigInfo () {
			$servers = [];
			foreach ( $this->getVarnishServersWithPorts () as $server ) {
				$handle = curl_init ( $server->host . ":" . $server->port . "/jetrails/varnish-config/versions" );
				curl_setopt ( $handle, CURLOPT_PORT, $server->port );
				curl_setopt ( $handle, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, true );
				curl_setopt ( $handle, CURLOPT_AUTOREFERER, true );
				curl_setopt ( $handle, CURLOPT_HEADER, true );
				curl_setopt ( $handle, CURLOPT_CONNECTTIMEOUT, 3 );
				curl_setopt ( $handle, CURLOPT_TIMEOUT, 3 );
				curl_setopt ( $handle, CURLOPT_MAXREDIRS, 0 );
				curl_setopt ( $handle, CURLOPT_CUSTOMREQUEST, "GET" );
				$response = curl_exec ( $handle );
				$responseCode = curl_getinfo ( $handle, CURLINFO_HTTP_CODE );
				curl_close ( $handle );
				$servers [] = (object) [
					"host" => $server->host,
					"port" => $server->port,
					"magento" => $responseCode == 200 && preg_match ( "/Magento (\d+\.\d+\.\d+(?:-p\d)?)/", $response, $match )
						? array_pop ( $match )
						: "Not Detected",
					"version" => $responseCode == 200 && preg_match ( "/Module (\d+\.\d+\.\d+)/", $response, $match )
						? array_pop ( $match )
						: "Not Detected",
				];
			}
			return $servers;
		}

		public function getExcludedRoutes () {
			$path = "jetrails_varnish/cache_exclusion_patterns/excluded_routes";
			$routes = explode ( "\n", $this->_getConfigValue ( $path ) );
			return array_filter ( $routes, function ( $i ) { return $i != ""; });
		}

		public function getExcludedWildcardPatterns () {
			$path = "jetrails_varnish/cache_exclusion_patterns/excluded_wildcard_patterns";
			$routes = explode ( "\n", $this->_getConfigValue ( $path ) );
			return array_filter ( $routes, function ( $i ) { return $i != ""; });
		}

		public function getExcludedRegExpPatterns () {
			$path = "jetrails_varnish/cache_exclusion_patterns/excluded_regexp_patterns";
			$routes = explode ( "\n", $this->_getConfigValue ( $path ) );
			return array_filter ( $routes, function ( $i ) { return $i != ""; });
		}

	}
