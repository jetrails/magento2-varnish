<?php

	namespace JetRails\Varnish\Block\Adminhtml\System\Config\Form;

	use Magento\Backend\Block\Template\Context;
	use Magento\Config\Block\System\Config\Form\Field;
	use Magento\Framework\Data\Form\Element\AbstractElement;

	class Export extends Field {

		protected $_template = "JetRails_Varnish::adminhtml/system/config/form/export.phtml";

		public function __construct ( Context $context, array $data = [] ) {
			parent::__construct ( $context, $data );
		}

		protected function _getElementHtml ( AbstractElement $element ) {
			return $this->_toHtml ();
		}

		public function render ( AbstractElement $element ) {
			$element->unsScope ()->unsCanUseWebsiteValue ()->unsCanUseDefaultValue ();
			return parent::render ( $element );
		}

		public function getActionUrl () {
			return $this->getUrl ("varnish/export/config");
		}

		public function getButtonHtml () {
			$button = $this->getLayout ()
			->createBlock ("Magento\Backend\Block\Widget\Button")
			->setData ([
				"label"	 	=> "Export VCL for Varnish 4",
				'onclick' 	=> "setLocation('{$this->getUrl('varnish/export/config')}')"
			]);
			return $button->toHtml ();
		}

	}