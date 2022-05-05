<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Configuration;

	use Magento\Backend\App\Action\Context;
	use Magento\Backend\App\Action;
	use Magento\Framework\View\Result\PageFactory;

	/**
	 * @version         3.0.2
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
			array $dataArray = []
		) {
			parent::__construct ( $context, $dataArray );
			$this->_resultPageFactory = $resultPageFactory;
		}

		protected function _isAllowed () {
			return $this->_authorization->isAllowed ("JetRails_Varnish::configuration");
		}

		public function execute () {
			return $this->_resultPageFactory->create ();
		}

	}
