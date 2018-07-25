<?php

	namespace JetRails\Varnish\Observer\Save;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\Message\ManagerInterface;
	use Magento\Store\Model\StoreManagerInterface;

	/**
	 * Product.php - This observer is triggered when the product save event is fired.  It then finds
	 * the url of the product and sends a URL purge request to the configured varnish servers.
	 * @version         1.1.3
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Product implements ObserverInterface {


		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         Data                _data               Instance of the Data helper class
		 * @var         Logger              _logger             Instance of the custom Logger class
		 * @var         ManagerInterface    _messageManager     Instance of the ManagerInterface
		 * @var         Purger              _purger             Instance of the Purger helper class
		 * @var         StoreManager        _storeManager       Instance of the StoreManager
		 */
		protected $_data;
		protected $_logger;
		protected $_messageManager;
		protected $_purger;
		protected $_storeManager;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       Data                data                Instance of the Data helper class
		 * @param       Logger              logger              Instance of the custom Logger class
		 * @param       ManagerInterface    messageManager      Instance of the ManagerInterface
		 * @param       Purger              purger              Instance of the Purger helper class
		 * @param       StoreManager        storeManager        Instance of the StoreManager
		 */
		public function __construct (
			Data $data,
			Logger $logger,
			ManagerInterface $messageManager,
			Purger $purger,
			StoreManagerInterface $storeManager
		) {
			// Save the injected class instances
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_messageManager = $messageManager;
			$this->_purger = $purger;
			$this->_storeManager = $storeManager;
		}

		/**
		 * This method is required because this class implements the ObserverInterface class.  This
		 * method gets executed when the registered event is fired for this class.  The event that
		 * this method will file for can be found in the events.xml file.
		 * @param       Observer            observer            Observer with event information
		 * @return      void
		 */
		public function execute ( Observer $observer ) {
			// Check to see if event is enabled
			if ( $this->_data->isEnabled () && $this->_data->shouldPurgeAfterProductSave () ) {
				// Get the base url for the current store
				$baseUrl = $this->_storeManager->getStore ()->getBaseUrl ();
				// Get the product id and retrieve all rewrites recursively and acyclicly.
				$pid = $observer->getProduct ()->getId ();
				$rewrites = $this->_purger->getUrlRewrites ("catalog/product/view/id/$pid");
				// Define purge command for each found URL
				$actions = array_fill_keys ( $rewrites, "purgeUrl" );
				$actions ["catalog/product/view/id/$pid/"] = "purgeStore"; // note the trailing slash
				// Loop through each rewrite
				foreach ( $actions as $rewrite => $command ) {
					// Validate the url
					$url = $this->_purger->validateUrl ( $baseUrl . $rewrite );
					// Loop though responses, after purging store (store so we can use 'starts with' for url)
					foreach ( $this->_purger->{ $command } ( $url ) as $response ) {
						// Log what we are trying to do
						$message = [
							"status" => $response->status,
							"action" => "auto_purge:product_save",
							"target" => $response->target,
							"server" => $response->server
						];
						$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
						// Check to see if response was successful
						if ( $response->status == 200 ) {
							// Add success response message
							$targetHtml = "<font color='#79A22E' ><b>$response->target</b></font>";
							$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
							$message = "Successfully purged varnish cache for $targetHtml on $serverHtml";
							$this->_messageManager->addSuccess ( $message );
						}
						else {
							// Otherwise add an error message
							$targetHtml = "<font color='#E22626' ><b>$response->target</b></font>";
							$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
							$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
							$message = "Error Purging varnish cache for $targetHtml on $serverHtml with response code $statusHtml";
							$this->_messageManager->addError ( $message );
						}
					}
				}
			}
		}

	}
