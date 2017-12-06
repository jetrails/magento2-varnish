<?php

	namespace JetRails\Varnish\Observer\Save;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Cms\Helper\Page as CmsPage;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\Message\ManagerInterface;

	/**
	 * Page.php - This observer is triggered when the CMS page save event is fired.  It then finds
	 * the url of the CMS page and sends a URL purge request to the configured varnish servers.
	 * @version         1.1.1
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 */
	class Page implements ObserverInterface {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         Data                _data               Instance of the Data helper class
		 * @var         Logger              _logger             Instance of the custom Logger class
		 * @var         ManagerInterface    _messageManager     Instance of the ManagerInterface
		 * @var         CmsPage             _page               Instance of the CmsPage
		 * @var         Purger              _purger             Instance of the Purger helper class
		 */
		protected $_data;
		protected $_logger;
		protected $_messageManager;
		protected $_page;
		protected $_purger;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       Data                data                Instance of the Data helper class
		 * @param       Logger              logger              Instance of the custom Logger class
		 * @param       ManagerInterface    messageManager      Instance of the ManagerInterface
		 * @param       CmsPage             page                Instance of the CmsPage
		 * @param       Purger              purger              Instance of the Purger helper class
		 */
		public function __construct (
			Data $data,
			Logger $logger,
			CmsPage $page,
			ManagerInterface $messageManager,
			Purger $purger
		) {
			// Save injected class instances
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_page = $page;
			$this->_messageManager = $messageManager;
			$this->_purger = $purger;
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