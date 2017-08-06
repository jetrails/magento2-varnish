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
	 * @version         1.1.0
	 * @package         JetRails® Varnish
	 * @category        Observer
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
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
			if ( $this->_data->isDebugMode () ) {
				$this->_response->setHeader ( "JetRails-Debug", "true", true );
			}
			// Get the values stored in the store configurations
			$routes = $this->_data->getExcludedRoutes ();
			$paths = $this->_data->getExcludedUrls ();
			// Construct the current route
			$routeName      = $this->_request->getRouteName ();
			$moduleName     = $this->_request->getModuleName ();
			$controllerName = $this->_request->getControllerName ();
			$actionName     = $this->_request->getActionName ();
			$constructedRoute = "$routeName/$moduleName/$controllerName/$actionName/";
			// Loop through each route and check if we should exclude
			foreach ( $routes as $route ) {
				if ( $route [ strlen ( $route ) - 1 ] != "/" ) $route .= "/";
				if ( strpos ( $constructedRoute, $route ) === 0 ) {
					$route = trim ( $route, "/" );
					$this->_response->setHeader ( "JetRails-No-Cache-Blame-Route", $route, true );
					break;
				}
			}
			// Construct the current url for the request
			$currentUrl = parse_url ( $this->_url->getCurrentUrl (), PHP_URL_PATH );
			$currentUrl = $currentUrl === "" ? "/" : $currentUrl;
			// Loop through each path and see if we should exclude
			foreach ( $paths as $path ) {
				if ( $currentUrl == $path ) {
					$this->_response->setHeader ( "JetRails-No-Cache-Blame-Url", $path, true );
					break;
				}
			}

		}

	}