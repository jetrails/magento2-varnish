<?php

	namespace JetRails\Varnish\Block\Adminhtml\System\Config\Form;

	use Magento\Config\Block\System\Config\Form\Field;
	use Magento\Framework\Data\Form\Element\AbstractElement;

	/**
	 * Export.php - This block is used to generate the HTML button in the store configuration
	 * section of magento.  The button is described as a frontend model in the system.xml file.
	 * @version         1.0.0
	 * @package         JetRails® Varnish
	 * @category        Form
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 */
	class Export extends Field {

		/**
		 * This variable contains the path to the template file for the export button.
		 * @var         String              _template           Path to template file for button
		 */
		protected $_template = "JetRails_Varnish::adminhtml/system/config/form/export.phtml";

		/**
		 * This method basically redirects the output of the _toHtml method in the parent class to
		 * the output of this method.
		 * @param       AbstractElement     element             This is ignored and unused
		 * @return      String                                  Button HTML
		 */
		protected function _getElementHtml ( AbstractElement $element ) {
			// Return the result of the _toHtml method in parent class
			return $this->_toHtml ();
		}

		/**
		 * This method is used within the template, and it returns the url for the action event.
		 * @return      String                                  Controller URL for action event
		 */
		public function getActionUrl () {
			// Construct the url form the controller path
			return $this->getUrl ("varnish/export/config");
		}

		/**
		 * This method is used within the template file and it simply constructs a button widget and
		 * set's the button's properties.
		 * @return      String                                  The button HTML with properties set
		 */
		public function getButtonHtml () {
			// Create a button widget and set it's properties
			$button = $this->getLayout ()
			->createBlock ("Magento\Backend\Block\Widget\Button")
			->setData ([
				"label"     => "Export VCL for Varnish 4",
				"onclick"   => "setLocation('{$this->getUrl('varnish/export/config')}')"
			]);
			// Return the constructed button's HTML
			return $button->toHtml ();
		}

	}