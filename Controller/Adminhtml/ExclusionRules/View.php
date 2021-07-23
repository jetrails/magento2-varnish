<?php

	namespace JetRails\Varnish\Controller\Adminhtml\ExclusionRules;

	use Magento\Backend\App\Action\Context;
	use Magento\Backend\App\Action;
	use Magento\Framework\View\Result\PageFactory;

	/**
	 * @version         2.0.0
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
			return $this->_authorization->isAllowed ("JetRails_Varnish::exclusion_rules");
		}

		public function execute () {
			return $this->_resultPageFactory->create ();
		}

	}
