<?php

	namespace JetRails\Varnish\Block\Adminhtml\System\Config\Form;

	use Magento\Backend\Block\Template\Context;
	use Magento\Config\Block\System\Config\Form\Field;
	use Magento\Framework\Data\Form\Element\AbstractElement;

	class Link extends Field {

		protected $_template = "JetRails_Varnish::adminhtml/system/config/form/link.phtml";

		protected function _getElementHtml ( AbstractElement $element ) {
			return $this->_toHtml ();
		}

		public function getActionUrl () {
			return $this->getUrl ("adminhtml/cache/index");
		}

	}