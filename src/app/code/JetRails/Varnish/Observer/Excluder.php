<?php

	namespace JetRails\Varnish\Observer;

	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\ResponseInterface;
	use Magento\Framework\App\RequestInterface;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\UrlInterface;

	/**
	 * Excluder.php - This class observes the event before a controller action is triggered.  It
	 * then looks at the URL and route of the controller action in question, and it checks the store
	 * config for excluded URLs and routes.  If any match, then a header parameter is set in order
	 * not to cache the page.
	 * @version         1.1.11
	 * @package         JetRails® Varnish
	 * @category        Observer
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Excluder implements ObserverInterface {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         Data                _data               Instance of the Data helper class
		 * @var         ResponseInterface   _response           Instance of the ResponseInterface
		 * @var         RequestInterface    _request            Instance of the RequestInterface
		 * @var         UrlInterface        _url                Instance of the UrlInterface
		 */
		protected $_data;
		protected $_response;
		protected $_request;
		protected $_url;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       Data                data                Instance of the Data helper class
		 * @param       ResponseInterface   response            Instance of the ResponseInterface
		 * @param       RequestInterface    request             Instance of the RequestInterface
		 * @param       UrlInterface        url                 Instance of the UrlInterface
		 */
		public function __construct (
			Data $data,
			ResponseInterface $response,
			RequestInterface $request,
			UrlInterface $url
		) {
			// Save the dependency injections internally
			$this->_data = $data;
			$this->_response = $response;
			$this->_request = $request;
			$this->_url = $url;
		}

		/**
		 * Given a set of routes, traverse through them and try to match it to the current value.
		 * If any match, the method short-circutes and returns true, false otherwise.
		 * @param       Array               routes              Instance of the Data helper class
		 * @param       String              currentRoute        Current route to match against
		 * @return      Boolean                                 Did any match?
		 */
		function _processRoutes ( $routes, $currentRoute ) {
			foreach ( $routes as $route ) {
				if ( $route [ strlen ( $route ) - 1 ] != "/" ) $route .= "/";
				if ( strpos ( $currentRoute, $route ) === 0 ) {
					$route = trim ( $route, "/" );
					$this->_response->setHeader ( "JetRails-No-Cache-Blame-Route", $route, true );
					return true;
				}
			}
			return false;
		}

		/**
		 * Given a set of paths, traverse through them and try to match it to the current value.
		 * If any match, the method short-circutes and returns true, false otherwise.
		 * @param       Array               paths               Instance of the Data helper class
		 * @param       String              currentPath         Current path to match against
		 * @return      Boolean                                 Did any match?
		 */
		function _processPaths ( $paths, $currentPath ) {
			foreach ( $paths as $path ) {
				if ( $currentPath == $path ) {
					$this->_response->setHeader ( "JetRails-No-Cache-Blame-Path", $path, true );
					return true;
				}
			}
			return false;
		}

		/**
		 * Given a set of patterns, traverse through them and try to match it to the current value.
		 * If any match, the method short-circutes and returns true, false otherwise.
		 * @param       Array               patterns               Instance of the Data helper class
		 * @param       String              currentPath         Current pattern to match against
		 * @return      Boolean                                 Did any match?
		 */
		function _processWildcards ( $patterns, $currentPath ) {
			foreach ( $patterns as $pattern ) {
				$regexp = str_replace (
					[ "\*\*", "\*" ],
					[ ".*", "[^\/]*" ],
					preg_quote ( $pattern, "/" )
				);
				if ( @preg_match ( "/^$regexp\$/m", $currentPath ) ) {
					$this->_response->setHeader ( "JetRails-No-Cache-Blame-Wildcard", $pattern, true );
					return true;
				}
			}
			return false;
		}

		/**
		 * Given a set of patterns, traverse through them and try to match it to the current value.
		 * If any match, the method short-circutes and returns true, false otherwise.
		 * @param       Array               patterns            Instance of the Data helper class
		 * @param       String              currentPath         Current pattern to match against
		 * @return      Boolean                                 Did any match?
		 */
		function _processRegExps ( $patterns, $currentUrl ) {
			foreach ( $patterns as $pattern ) {
				if ( @preg_match ( $pattern, $currentUrl ) ) {
					$this->_response->setHeader ( "JetRails-No-Cache-Blame-RegExp", $pattern, true );
					return true;
				}
			}
			return false;
		}

		/**
		 * This method is required because this class implements the ObserverInterface class.  This
		 * method gets executed when the registered event is fired for this class.  The event that
		 * this method will file for can be found in the events.xml file.
		 * @param       Observer            observer            Observer with event information
		 * @return      void
		 */
		public function execute ( Observer $observer ) {
			// If the module is disabled, then exit
			if ( !$this->_data->isEnabled () ) return;
			// Check to see if debug mode is set, if it is set the flag
			$isDebugMode = $this->_data->isDebugMode ();
			if ( $isDebugMode ) {
				$this->_response->setHeader ( "JetRails-Debug", "true", true );
			}
			// Get the values stored in the store configurations
			$routes = $this->_data->getExcludedRoutes ();
			$paths = $this->_data->getExcludedUrls ();
			$wildcards = $this->_data->getExcludedWildcardPatterns ();
			$regexps = $this->_data->getExcludedRegExpPatterns ();
			// Set the module version
			$version = $this->_data->getModuleVersion ();
			$this->_response->setHeader ( "JetRails-Version", $version, true );
			// Construct the current route
			$routeName      = $this->_request->getRouteName ();
			$moduleName     = $this->_request->getModuleName ();
			$controllerName = $this->_request->getControllerName ();
			$actionName     = $this->_request->getActionName ();
			// Get current variables and set them in headers if debug mode is on
			$currentUrl   = $this->_url->getCurrentUrl ();
			$currentPath  = parse_url ( $currentUrl, PHP_URL_PATH );
			$currentPath  = $currentPath === "" ? "/" : $currentPath;
			$currentRoute = "$routeName/$moduleName/$controllerName/$actionName/";
			if ( $isDebugMode ) {
				$this->_response->setHeader ( "JetRails-Current-Url", $currentUrl, true );
				$this->_response->setHeader ( "JetRails-Current-Path", $currentPath, true );
				$this->_response->setHeader ( "JetRails-Current-Route", trim ( $currentRoute, "/" ), true );
			}
			// Loop through and attempt to exclude, shortcircut for speed
			$hasExclusion = false
				|| $this->_processRoutes ( $routes, $currentRoute )
				|| $this->_processPaths ( $paths, $currentPath )
				|| $this->_processWildcards ( $wildcards, $currentPath )
				|| $this->_processRegExps ( $regexps, $currentUrl );
		}

	}
