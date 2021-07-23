<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Core;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Helper\Validator;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\App\Action\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\Controller\ResultFactory;

	/**
	 * @version         2.0.0
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	abstract class PurgeCache extends Action {

		protected $_data;
		protected $_validator;
		protected $_logger;
		protected $_purger;
		protected $_successMessages;
		protected $_warningMessages;
		protected $_errorMessages;

		public function __construct (
			Context $context,
			Data $data,
			Validator $validator,
			Logger $logger,
			Purger $purger
		) {
			parent::__construct ( $context );
			$this->_data = $data;
			$this->_validator = $validator;
			$this->_logger = $logger;
			$this->_purger = $purger;
			$this->_successMessages = [];
			$this->_warningMessages = [];
			$this->_errorMessages = [];
		}

		protected function _isAllowed () {
			return $this->_authorization->isAllowed ("JetRails_Varnish::purge_cache");
		}

		protected function _consumeMessages () {
			if ( count ( $this->_successMessages ) > 0 ) {
				$this->messageManager->addSuccess ( implode ( "</br>", $this->_successMessages ) );
				$this->_successMessages = [];
			}
			if ( count ( $this->_warningMessages ) > 0 ) {
				$this->messageManager->addWarning ( implode ( "</br>", $this->_warningMessages ) );
				$this->_warningMessages = [];
			}
			if ( count ( $this->_errorMessages ) > 0 ) {
				$this->messageManager->addError ( implode ( "</br>", $this->_errorMessages ) );
				$this->_errorMessages = [];
			}
		}

		abstract protected function _run ();

		public function execute () {
			if ( $this->_data->isEnabled () ) {
				$this->_run ();
				$this->_consumeMessages ();
			}
			else {
				$this->messageManager->addError (
					"Cache application must be set to <b>Varnish Cache™</b>, set it by configuring" .
					" <b>Stores &#10095; Settings &#10095; Configuration &#10095; Advanced &#10095; System &#10095; Full Page Cache &#10095; Caching Application</b>"
				);
			}
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			return $redirect->setPath ("varnish/purgecache/view");
		}

	}
