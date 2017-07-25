<?php

	namespace JetRails\Varnish\Observer\Save;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Cms\Helper\Page as CmsPage;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\Message\ManagerInterface;

	class Page implements ObserverInterface {

	    protected $_data;
	    protected $_logger;
	    protected $_messageManager;
	    protected $_page;
	    protected $_purger;

	    public function __construct (
	        Data $data,
	        Logger $logger,
	        CmsPage $page,
	        ManagerInterface $messageManager,
	        Purger $purger
	    ) {
	        $this->_data = $data;
	        $this->_logger = $logger;
	        $this->_page = $page;
	        $this->_messageManager = $messageManager;
	        $this->_purger = $purger;
	    }

		public function execute ( Observer $observer ) {
			// Check to see if event is enabled
			if ( $this->_data->isEnabled () && $this->_data->shouldPurgeAfterCmsPageSave () ) {
				// Get the page url
				$pageUrl = $this->_page->getPageUrl ( $observer->getPage ()->getId () );
				$pageUrl = reset ( ( explode ( "?", $pageUrl ) ) );
				// Validate the url
				$url = $this->_purger->validateUrl ( $pageUrl );
				// Loop though responses
				foreach ( $this->_purger->purgeUrl ( $url ) as $response ) {
					// Log what we are trying to do
					$message = [
						"status" => $response->status,
						"action" => "auto_purge:cms_save",
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