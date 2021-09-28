<?php

	namespace JetRails\Varnish\Logger;

	use Monolog\Logger as MonoLogger;

	/**
	 * @version         2.0.2
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Logger extends MonoLogger {

		public function blame ( $info, $message, array $context = [] ) {
			$message [ "blame" ] = $info;
			parent::info ( strtolower ( json_encode ( $message ) ), $context );
		}

	}
