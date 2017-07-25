<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Purge;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\App\Action\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\Controller\ResultFactory;

	class All extends Action {

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
			// Ask to purge and iterate over responses
			foreach ( $this->_purger->purgeAll () as $response ) {
				// Log what we are trying to do
				$message = [
					"action" => "purge:all",
					"status" => $response->status,						
					"server" => $response->server
				];
				$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
				// Check to see if response was successful
				if ( $response->status == 200 ) {
					// Add success response message
					$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
					$msg = "Successfully purged all cache on $serverHtml";
					$this->messageManager->addSuccess ( $msg );
				}
				else {
					// Otherwise add an error message
					$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
					$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
					$msg = "Error Purging all cache on $serverHtml with response code $statusHtml";
					$this->messageManager->addError ( $msg );
				}
			}
			// Redirect back to cache management page
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			return $redirect->setPath ("adminhtml/cache/index");
		}

	}