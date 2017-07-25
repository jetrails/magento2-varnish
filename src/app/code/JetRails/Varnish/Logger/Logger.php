<?php

	namespace JetRails\Varnish\Logger;

	use Monolog\Logger as MonoLogger;

	class Logger extends MonoLogger {

		public function blame ( $info, $message, array $context = [] ) {
			// Append user information to the message
			$message [ "blame" ] = $info;
			// Ask the original method to handle it
        	parent::info ( strtolower ( json_encode ( $message ) ), $context );
    	}

	}