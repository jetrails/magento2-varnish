<?php

	namespace JetRails\Varnish\Observer\Save;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\Message\ManagerInterface;
	use Magento\Catalog\Helper\Product as ProductHelper;

	class Product implements ObserverInterface {

	    protected $_data;
	    protected $_logger;
	    protected $_messageManager;
	    protected $_productHelper;
	    protected $_purger;

	    public function __construct (
	        Data $data,
	        Logger $logger,
	        ManagerInterface $messageManager,
	        ProductHelper $productHelper,
	        Purger $purger
	    ) {
	        $this->_data = $data;
	       	$this->_logger = $logger;
	       	$this->_messageManager = $messageManager;
	        $this->_productHelper = $productHelper;
	        $this->_purger = $purger;
	    }

		public function execute ( Observer $observer ) {
			// Check to see if event is enabled
			if ( $this->_data->isEnabled () && $this->_data->shouldPurgeAfterProductSave () ) {
				// Get the product and the product url specific to store view
				$product = $observer->getProduct ();
				$productUrl = $this->_productHelper->getProductUrl ( $product->getId () );
				$productUrl = reset ( ( explode ( "/key/", $productUrl ) ) );
				// Validate the url
				$url = $this->_purger->validateUrl ( $productUrl );
				// Loop though responses, after purging store (store so we can use starts with for url)
				foreach ( $this->_purger->purgeStore ( $url ) as $response ) {
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