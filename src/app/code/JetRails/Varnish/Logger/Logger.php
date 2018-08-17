<?php

	namespace JetRails\Varnish\Logger;

	use Monolog\Logger as MonoLogger;

	/**
	 * Logger.php - This class extends the MonoLogger class and adds a method called blame.  This
	 * method in essence is the same as the info method because it logs with the same severity, but
	 * it also takes in additional blame oriented data in order to propagate blame to the caller as
	 * well as the action that was taken.
	 * @version         1.1.5
	 * @package         JetRails® Varnish
	 * @category        Logger
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Logger extends MonoLogger {

		/**
		 * This method takes in user information and logging information and creates an entry within
		 * the custom log file with INFO level severity.  It is called blame because this is what
		 * this log file is here to do.  Caller information is saved when a certain action is
		 * triggered and it is logged within this custom log file.
		 * @param       Object              info                Caller information for blame data
		 * @param       Object              message             The elements to log, parameterized
		 * @param       Array               context             Additional context data to pass
		 * @return      void
		 */
		public function blame ( $info, $message, array $context = [] ) {
			// Append user information to the message
			$message [ "blame" ] = $info;
			// Ask the original method to handle it
			parent::info ( strtolower ( json_encode ( $message ) ), $context );
		}

	}
