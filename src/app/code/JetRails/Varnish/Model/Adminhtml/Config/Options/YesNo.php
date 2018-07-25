<?php

	namespace JetRails\Varnish\Model\Adminhtml\Config\Options;

	use Magento\Framework\Option\ArrayInterface;

	/**
	 * YesNo.php - These options include yes and no.  Yes has an integer value of 1 and no has an
	 * integer value of 0.
	 * @version         1.1.4
	 * @package         JetRails® Varnish
	 * @category        Options
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class YesNo implements ArrayInterface {

		/**
		 * These statically defined class constants are used throughout the module and referenced
		 * instead of referencing the value attached to them in order to be able to change the
		 * values of these constants and not have to change them anywhere else.
		 */
		const YES = 1;
		const NO = 0;

		/**
		 * This method is required because this class implements the ArrayInterface parent class.
		 * This method returns an array of options to display in the select menu in the store config
		 * page of the store.  The frontend model can be fond referenced in the system.xml file.
		 * @return      Array                                   Select menu options with labels
		 */
		public function toOptionArray () {
			// Return the options in array form (label/value)
			return [
				[ "value" => self::YES, "label" => "Yes" ],
				[ "value" => self::NO, "label" => "No" ]
			];
		}

	}