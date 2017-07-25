<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Purge;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\App\Action\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\Controller\ResultFactory;

	class Store extends Action {

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
				// Load passed store id and validate it's existence
				$storeId = intval ( $this->getRequest ()->getParam ("id") );
				// Make sure store id is valid
				$url = $this->_purger->validateAndResolveStoreId ( $storeId );
				if ( gettype ( $url ) == "object" ) {
					// Ask to purge and iterate over responses
					foreach ( $this->_purger->purgeStore ( $url ) as $response ) {
						// Log what we are trying to do
						$message = [
							"status" => $response->status,
							"action" => "purge:store",
							"target" => $response->target,
							"server" => $response->server
						];
						$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
						// Check to see if response was successful
						if ( $response->status == 200 ) {
							// Add success response message
							$storeHtml = "<font color='#79A22E' ><b>$response->target</b></font>";
							$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
							$msg = "Successfully purged store view $storeHtml on $serverHtml";
							$this->messageManager->addSuccess ( $msg );
						}
						else {
							// Otherwise add an error message
							$storeHtml = "<font color='#E22626' ><b>$response->target</b></font>";
							$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
							$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
							$msg = "Error Purging store view $storeHtml on $serverHtml with response code $statusHtml";
							$this->messageManager->addError ( $msg );
						}
					}
				}
				// If it is invalid, warn caller
				else { $this->messageManager->addError ( "Invalid store id '$storeId' passed" ); }
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