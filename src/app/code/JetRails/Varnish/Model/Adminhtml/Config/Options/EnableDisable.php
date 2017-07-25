<?php

	namespace JetRails\Varnish\Model\Adminhtml\Config\Options;

	use Magento\Framework\Option\ArrayInterface;

	class EnableDisable implements ArrayInterface {

		const DISABLED = 0;
		const ENABLED = 1;

		public function toOptionArray () {
			// Return the options in array form (label/value)
			return [
			    [ "value" => 1, "label" => "Enabled" ],
				[ "value" => 0, "label" => "Disabled" ]
			];
		}

	}