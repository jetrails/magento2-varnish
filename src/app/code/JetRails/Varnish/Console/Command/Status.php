<?php

	namespace JetRails\Varnish\Console\Command;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputInterface;

	/**
	 * Status.php - This class inherits from the AbstractCommand.  This command tells the user if
	 * the module is enabled.  In the sense that the 'Caching Application' setting is set to
	 * 'Varnish Cache', and not 'Built-in Cache'.
	 * @version         1.1.11
	 * @package         JetRails® Varnish
	 * @category        Command
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Status extends AbstractCommand {

		/**
		 * This method is overloaded because of the Command class that was originally defined the
		 * Symfony package.  Inside we set the command name and set the command description.
		 * @return      void
		 */
		protected function configure () {
			// Register the command and set the arguments
			$this->setName ("varnish:status")
			->setDescription ("Check to see what caching application is being used");
		}

		/**
		 * This method is defined because it is required by the abstract parent class.  It takes in
		 * an input interface and actually does all the work.  It then returns an object that
		 * defines the response that will be displayed in the console.
		 * @param       InputInterface      input               The input interface with the console
		 * @return      Object
		 */
		protected function runCommand ( InputInterface $input ) {
			// Check to see if varnish caching is enabled and prepare message
			$status = $this->_data->isEnabled ();
			$message = $status ? "Varnish Cache™" : "Built-in Cache";
			$message = "<options=underscore>$message</>";
			$message = "Caching application is set to $message";
			// Return the message and status to caller
			return [ "status" => $status, "message" => $message ];
		}

	}
