<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Purge;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\App\Action\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\Controller\ResultFactory;

	class Url extends Action {

		protected $_data;
		protected $_logger;
		protected $_purger;

		public function __construct (
			Context $context,
			Data $data,
			Logger $logger,
			Purger $purger 
		) {
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_purger = $purger;
			parent::__construct ( $context );
		}

		public function execute () {
			// Check to see if varnish cache is enabled
			if ( $this->_data->isEnabled () ) {
				// Load passed url parameter and validate it
				$url = $this->getRequest ()->getParam ("url");
				$url = $this->_purger->validateUrl ( $url );
				// If an object was returned, then it was a valid url
				if ( gettype ( $url ) == "object" ) {
					// Ask to purge and iterate over responses
					foreach ( $this->_purger->purgeUrl ( $url ) as $response ) {
						// Log what we are trying to do
						$message = [
							"status" => $response->status,
							"action" => "purge:url",
							"target" => $response->target,
							"server" => $response->server
						];
						$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
						// Check to see if response was successful
						if ( $response->status == 200 ) {
							// Add success response message
							$targetHtml = "<font color='#79A22E' ><b>$response->target</b></font>";
							$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
							$message = "Successfully purged url $targetHtml on $serverHtml";
							$this->messageManager->addSuccess ( $message );
						}
						else {
							// Otherwise add an error message
							$targetHtml = "<font color='#E22626' ><b>$response->target</b></font>";
							$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
							$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
							$message = "Error purging url $targetHtml on $serverHtml with response code $statusHtml";
							$this->messageManager->addError ( $message );
						}
					}
				}
				// Otherwise an error was returned in the form of a string
				else { $this->messageManager->addError ( $url ); }
			}
			else {
				// Cache application is not Varnish, warn user
				$this->messageManager->addError (
					"Cache application must be set to <b>Varnish Cache</b>, set it by configuring" .
					" <b>Stores → Advanced → Developer → System → Full Page Cache → Caching Application</b>"
				);
			}
			// Redirect back to cache management page
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
        	return $redirect->setPath ("adminhtml/cache/index");
		}

	}