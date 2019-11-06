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
	 * AutoPurge.php - This abstract class is in place to contain some necessary methods that
	 * in all sub classes. These classes must implement the execute method since this class
	 * extends from the Magento Action class.
	 * @version         1.1.10
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
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
		 * @var         Array               _messages           Saved messages for dumping together
		 */
		protected $_data;
		protected $_logger;
		protected $_messageManager;
		protected $_purger;
		protected $_storeManager;
		protected $_messages;

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
			$this->_messages = [];
		}

		/**
		 * This method takes in a target url to purge and it traverses through all the configured
		 * varnish servers and asks them to purge said url. An optional flag is passed to purge
		 * all urls that contain the given string.
		 * @var         Boolean             purgeChildren       Purge all URLS that contain target?
		 * @param       String              target              The URL to purge
		 * @return      void
		 */
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

		/**
		 * This method takes in a route and it looks through the rewrites table for all urls that lead
		 * to said url. It uses a passed store object to determine the base url to use.
		 * @param       String              route               The route to look for in rewrites table
		 * @param       Object              store               The store to use for base url
		 * @return      void
		 */
		protected function _purgeUsingStoreObject ( $route, $store ) {
			// Get the base url for the current store
			$baseUrl = $store->getBaseUrl ();
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
					array_push ( $this->_messages, $target );
				}
			}
		}

		/**
		 * This method takes in some header text and appends all the saved messages to it. It then
		 * uses the message manager to send the message together.
		 * @param       String              header              The header to append to the message\
		 * @return      void
		 */
		protected function _dumpCombinedMessages ( $header ) {
			if ( count ( $this->_messages ) > 0 ) {
				$header = "<font color='#79A22E' ><b>$header</b></font>";
				array_unshift ( $this->_messages, $header );
				$this->_messageManager->addSuccess ( implode ( "</br>", $this->_messages ) );
				$this->_messages = [];
			}
		}

		/**
		 * This method takes in a route and determines if the current scope is the "All Stores Scope".
		 * If it is, then all store views are purged, otherwise just the current one is purged.
		 * @param       String              route               The route to look for in rewrites table
		 * @return      void
		 */
		protected function _purgeUsingRoute ( $route ) {
			if ( $this->_storeManager->getStore ()->getId () === "0" ) {
				$stores = $this->_storeManager->getStores ();
				foreach ( $stores as $store ) {
					$this->_purgeUsingStoreObject ( $route, $store );
				}
			}
			else {
				$store = $this->_storeManager->getStore ();
				$this->_purgeUsingStoreObject ( $route, $store );
			}
		}

		/**
		 * This method is abstract because this class implements the Action class.
		 * @param       Observer            observer            Observer object passed on dispatch
		 * @return      void
		 */
		public abstract function execute ( Observer $observer );

	}
