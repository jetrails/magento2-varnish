<?php

	namespace JetRails\Varnish\Controller\Adminhtml\PurgeCache;

	use JetRails\Varnish\Helper\Data;
	use Magento\Backend\App\Action\Context;
	use Magento\Backend\App\Action;
	use Magento\Framework\View\Result\PageFactory;
	use Magento\Framework\Controller\ResultFactory;

	/**
	 * @version         3.0.3
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class View extends Action {

		protected $_data;
		protected $_resultPageFactory;

		public function __construct (
			Data $data,
			Context $context,
			PageFactory $resultPageFactory,
			array $dataArray = []
		) {
			parent::__construct ( $context, $dataArray );
			$this->_resultPageFactory = $resultPageFactory;
			$this->_data = $data;
		}

		protected function _isAllowed () {
			return $this->_authorization->isAllowed ("JetRails_Varnish::purge_cache");
		}

		public function execute () {
			if ( $this->_data->isEnabled () ) {
				return $this->_resultPageFactory->create ();
			}
			else {
				$this->messageManager->addError (
					"Before using the <b>Purge Cache</b> feature, <b>Varnish Cache™</b>, must be set-up."
				);
			}
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			return $redirect->setPath ("varnish/configuration/view");
		}

	}
