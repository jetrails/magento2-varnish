<?php

	namespace JetRails\Varnish\Observer;

	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\ResponseInterface;
	use Magento\Framework\App\RequestInterface;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\UrlInterface;

	/**
	 * @version         2.0.2
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Excluder implements ObserverInterface {

		protected $_data;
		protected $_response;
		protected $_request;
		protected $_url;

		public function __construct (
			Data $data,
			ResponseInterface $response,
			RequestInterface $request,
			UrlInterface $url
		) {
			$this->_data = $data;
			$this->_response = $response;
			$this->_request = $request;
			$this->_url = $url;
		}

		protected function _processRoutes ( $routes, $currentRoute ) {
			foreach ( $routes as $route ) {
				if ( $route [ strlen ( $route ) - 1 ] != "/" ) $route .= "/";
				if ( strpos ( $currentRoute, $route ) === 0 ) {
					$route = trim ( $route, "/" );
					$this->_response->setHeader ( "JR-Exclude-By", "route", true );
					$this->_response->setHeader ( "JR-Exclude-With", $route, true );
					return true;
				}
			}
			return false;
		}

		protected function _processWildcards ( $patterns, $currentPath ) {
			foreach ( $patterns as $pattern ) {
				$regexp = str_replace (
					[ "\*\*", "\*" ],
					[ ".*", "[^\/]*" ],
					preg_quote ( $pattern, "/" )
				);
				if ( @preg_match ( "/^$regexp\$/m", $currentPath ) ) {
					$this->_response->setHeader ( "JR-Exclude-By", "wildcard", true );
					$this->_response->setHeader ( "JR-Exclude-With", $pattern, true );
					return true;
				}
			}
			return false;
		}

		protected function _processRegExps ( $patterns, $currentUrl ) {
			foreach ( $patterns as $pattern ) {
				if ( @preg_match ( $pattern, $currentUrl ) ) {
					$this->_response->setHeader ( "JR-Exclude-By", "regexp", true );
					$this->_response->setHeader ( "JR-Exclude-With", $pattern, true );
					return true;
				}
			}
			return false;
		}

		public function execute ( Observer $observer ) {
			if ( !$this->_data->isEnabled () ) return;
			$isDebugMode = $this->_data->isDebugMode ();
			$routes = $this->_data->getExcludedRoutes ();
			$wildcards = $this->_data->getExcludedWildcardPatterns ();
			$regexps = $this->_data->getExcludedRegExpPatterns ();
			$version = $this->_data->getModuleVersion ();
			$routeName      = $this->_request->getRouteName ();
			$moduleName     = $this->_request->getModuleName ();
			$controllerName = $this->_request->getControllerName ();
			$actionName     = $this->_request->getActionName ();
			$currentUrl   = trim ( $this->_url->getCurrentUrl (), "/" );
			$currentPath  = parse_url ( $currentUrl, PHP_URL_PATH );
			$currentPath  = $currentPath === "" ? "/" : $currentPath;
			$currentRoute = "$routeName/$moduleName/$controllerName/$actionName/";
			$this->_response->setHeader ( "JR-Version", $version, true );
			$this->_response->setHeader ( "JR-Debug", $isDebugMode ? "true" : "false", true );
			$this->_response->setHeader ( "JR-Current-Url", $currentUrl, true );
			$this->_response->setHeader ( "JR-Current-Path", $currentPath, true );
			$this->_response->setHeader ( "JR-Current-Route", trim ( $currentRoute, "/" ), true );
			$hasExclusion = false
				|| $this->_processRoutes ( $routes, $currentRoute )
				|| $this->_processWildcards ( $wildcards, $currentPath )
				|| $this->_processRegExps ( $regexps, $currentUrl );
		}

	}
