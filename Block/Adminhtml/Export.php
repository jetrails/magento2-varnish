<?php

	namespace JetRails\Varnish\Block\Adminhtml;

	use Magento\Backend\Block\Widget\Button;
	use Magento\Config\Block\System\Config\Form\Field;
	use Magento\Framework\Data\Form\Element\AbstractElement;
	use Magento\PageCache\Model\Config;

	/**
	 * @version         3.0.4
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Export extends Field {

		protected function _getElementHtml ( AbstractElement $element) {
			$buttonBlock = $this->getForm ()->getLayout ()->createBlock ( Button::class );
			$params = [
				"website" => $buttonBlock->getRequest ()->getParam ("website"),
				"varnish" => $this->getVarnishVersion ()
			];
			$data = [
				"id" => "system_full_page_cache_varnish_export_custom_button_version" . $this->getVarnishVersion (),
				"label" => $this->getLabel (),
				"onclick" => "setLocation ('" . $this->getVarnishUrl ( $params ) . "')",
			];
			$html = $buttonBlock->setData ( $data )->toHtml ();
			return $html;
		}

		public function getVarnishVersion () {
			return 0;
		}

		public function getLabel () {
			return  __( "Export Custom VCL for Varnish %1", $this->getVarnishVersion () );
		}

		public function getVarnishUrl ( $params = [] ) {
			return $this->getUrl ( "varnish/export/customconfig", $params );
			// return $this->getUrl ( "*/Export/customConfig", $params );
		}

		public function getTtlValue () {
			return $this->_scopeConfig->getValue ( Config::XML_PAGECACHE_TTL );
		}

	}
