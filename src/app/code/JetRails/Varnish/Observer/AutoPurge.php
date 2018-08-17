<?php

	namespace JetRails\Varnish\Observer;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\Message\ManagerInterface;
	use Magento\Store\Model\StoreManagerInterface;

	/**
	 * AutoPurge.php -
	 *
	 *
	 *
	 *
	 * @version         1.1.4
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	abstract class AutoPurge implements ObserverInterface {


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

		protected function _purgeOnAllServers ( $target, $purgeChildren = false ) {
			$noErrors = true;
			$url = $this->_purger->validateUrl ( $target );
			$responses = $this->_purger->{ $purgeChildren ? "purgeStore" : "purgeUrl" } ( $url );
			foreach ( $responses as $response ) {
				// Log what we are trying to do
				$message = [
					"status" => $response->status,
					"action" => "auto_purge:product_save",
					"target" => $response->target,
					"server" => $response->server
				];
				$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
				// Check to see if response was successful
				if ( $response->status != 200 ) {
					// Otherwise add an error message
					$targetHtml = "<font color='#E22626' ><b>$response->target</b></font>";
					$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
					$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
					$message = "Error Purging varnish cache for $targetHtml on $serverHtml with response code $statusHtml";
					$this->_messageManager->addError ( $message );
					$noErrors = false;
				}
			}
			return $noErrors;
		}

		protected function _purgeUsingRoute ( $route ) {
			// Get the base url for the current store
			$baseUrl = $this->_storeManager->getStore ()->getBaseUrl ();
			// Get the category id and retrieve all rewrites recursively and acyclicly.
			$rewrites = $this->_purger->getUrlRewrites ( $route );
			// Loop through all rewrites
			foreach ( $rewrites as $rewrite ) {
				// Purge all relative urls from varnish servers
				$target = "$baseUrl$rewrite";
				$success = true;
				$success &= $this->_purgeOnAllServers ( $target, false );
				$success &= $this->_purgeOnAllServers ( rtrim ( $target, "/" ) . "/", true );
				$success &= $this->_purgeOnAllServers ( rtrim ( $target, "/" ) . "?", true );
				if ( $success ) {
					// Add success response message
					$targetHtml = "<font color='#79A22E' ><b>$target</b></font>";
					$serverHtml = "<font color='#79A22E' ><b>all varnish servers</b></font>";
					$message = "Successfully purged varnish cache for $targetHtml on $serverHtml";
					$this->_messageManager->addSuccess ( $message );
				}
			}
		}

		public abstract function execute ( Observer $observer );

	}