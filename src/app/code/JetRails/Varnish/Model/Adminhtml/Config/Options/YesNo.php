<?php

	namespace JetRails\Varnish\Model\Adminhtml\Config\Options;

	class YesNo implements \Magento\Framework\Option\ArrayInterface {

		const YES = 1;
		const NO = 0;

		public function toOptionArray () {
			// Return the options in array form (label/value)
			return [
			    [ "value" => self::YES, "label" => "Yes" ],
				[ "value" => self::NO, "label" => "No" ]
			];
		}

	}