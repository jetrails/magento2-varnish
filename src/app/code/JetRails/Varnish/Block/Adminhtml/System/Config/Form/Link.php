<?php

	namespace JetRails\Varnish\Block\Adminhtml\System\Config\Form;

	use Magento\Config\Block\System\Config\Form\Field;
	use Magento\Framework\Data\Form\Element\AbstractElement;

	/**
	 * Link.php - This block alongside the template is used to render a link to the cache management
	 * page in the magento store backend.  This block is also referenced as a frontend model in the
	 * system.xml file.
	 * @version         1.1.6
	 * @package         JetRails® Varnish
	 * @category        Form
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Link extends Field {

		/**
		 * This variable contains the path to the template file for the export button.
		 * @var         String              _template           Path to template file for button
		 */
		protected $_template = "JetRails_Varnish::adminhtml/system/config/form/link.phtml";

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
		 * @return      String                                  Controller URL for action
		 */
		public function getActionUrl () {
			// Construct the url form the controller path
			return $this->getUrl ("adminhtml/cache/index");
		}

	}
