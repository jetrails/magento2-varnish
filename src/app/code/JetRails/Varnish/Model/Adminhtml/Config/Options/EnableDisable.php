<?php

	namespace JetRails\Varnish\Model\Adminhtml\Config\Options;

	use Magento\Framework\Option\ArrayInterface;

	/**
	 * EnableDisable.php - These options include enable and disable with enable having an integer
	 * value of 1 and disable having an integer value of 0.
	 * @version         1.1.7
	 * @package         JetRails® Varnish
	 * @category        Options
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class EnableDisable implements ArrayInterface {

		/**
		 * These statically defined class constants are used throughout the module and referenced
		 * instead of referencing the value attached to them in order to be able to change the
		 * values of these constants and not have to change them anywhere else.
		 */
		const DISABLED = 0;
		const ENABLED = 1;

		/**
		 * This method is required because this class implements the ArrayInterface parent class.
		 * This method returns an array of options to display in the select menu in the store config
		 * page of the store.  The frontend model can be fond referenced in the system.xml file.
		 * @return      Array                                   Select menu options with labels
		 */
		public function toOptionArray () {
			// Return the options in array form (label/value)
			return [
				[ "value" => self::ENABLED, "label" => "Enabled" ],
				[ "value" => self::DISABLED, "label" => "Disabled" ]
			];
		}

	}
