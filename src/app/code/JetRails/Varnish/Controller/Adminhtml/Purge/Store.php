<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Purge;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\App\Action\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\Controller\ResultFactory;

	/**
	 * Store.php - This class is a controller action and when it is triggered, it is responsible for
	 * purging all the cache in all the configured cache servers that match (start with) the base
	 * url of the store view.  The store view id is passed as a parameter
	 * @version         1.1.8
	 * @package         JetRails® Varnish
	 * @category        Purge
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Store extends Action {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         Data                _data               Instance of the Data class
		 * @var         Logger              _logger             Instance of the Logger class
		 * @var         Purger              _purger             Instance of the Purger class
		 */
		protected $_data;
		protected $_logger;
		protected $_purger;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       Context             $context            The context of the caller
		 * @param       Data                $data               Instance of the Data helper class
		 * @param       Logger              $logger             Instance of the Logger class
		 * @param       Purger              $purger             Instance of the Purger class
		 */
		public function __construct (
			Context $context,
			Data $data,
			Logger $logger,
			Purger $purger
		) {
			// Run the parent constructor
			parent::__construct ( $context );
			// Save the injected class instances
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_purger = $purger;
		}

		/**
		 * This method is overloaded because the parent class Action requires it.  This method is
		 * triggered whenever the controller is reached.  It handles all the logic of the controller
		 * action.
		 * @return      ResultFactory                           ResultFactory redirect on success
		 */
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
					"Cache application must be set to <b>Varnish Cache™</b>, set it by configuring" .
					" <b>Stores → Advanced → Developer → System → Full Page Cache → Caching Application</b>"
				);
			}
			// Redirect back to cache management page
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			return $redirect->setPath ("adminhtml/cache/index");
		}

	}
