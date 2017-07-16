<?php

	namespace JetRails\Varnish\Model\Config\Options;

	class YesNo implements \Magento\Framework\Option\ArrayInterface {

 		/**
		 * 
		 * @return
		 */
		public function toOptionArray () {
			// Return the options in array form (label/value)
			return [
			    [ "value" => 1, "label" => "Yes" ],
				[ "value" => 0, "label" => "No" ]
			];
		}

	}