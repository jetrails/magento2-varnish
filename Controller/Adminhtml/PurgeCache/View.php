<?php

	namespace JetRails\Varnish\Controller\Adminhtml\PurgeCache;

	use Magento\Backend\App\Action\Context;
	use Magento\Backend\App\Action;
	use Magento\Framework\View\Result\PageFactory;

	/**
	 * @version         1.1.11
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class View extends Action {

		protected $_resultPageFactory;

		public function __construct (
			Context $context,
			PageFactory $resultPageFactory,
			array $data = []
		) {
			parent::__construct ( $context, $data );
			$this->_resultPageFactory = $resultPageFactory;
		}

		protected function _isAllowed () {
			return $this->_authorization->isAllowed ("JetRails_Varnish::purge_cache");
		}

		public function execute () {
			return $this->_resultPageFactory->create ();
		}

	}
