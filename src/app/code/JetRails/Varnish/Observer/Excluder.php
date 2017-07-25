<?php

	namespace JetRails\Varnish\Observer;

	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\ResponseInterface;
	use Magento\Framework\App\RequestInterface;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\UrlInterface;

	class Excluder implements ObserverInterface {

		protected $_data;
		protected $_response;
		protected $_request;
		protected $_url;

		public function __construct (
			Data $data,
			RequestInterface $request,
			ResponseInterface $response,
			UrlInterface $url
		) {
			// Save the dependency injections internally
			$this->_data = $data;
			$this->_response = $response;
			$this->_request = $request;
			$this->_url = $url;
		}

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