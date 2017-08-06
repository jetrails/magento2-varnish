<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputInterface;

	/**
	 * All.php - This class inherits from the AbstractCommand.  This command contacts all the
	 * configured varnish cache servers and asks them to flush all the cache for all urls.
	 * @version         1.0.0
	 * @package         JetRails® Varnish
	 * @category        Purge
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 */
	class All extends AbstractCommand {

		/**
		 * This internal data member defines if the action should be run if the feature is disabled
		 * in the store config.
		 * @var         Boolean             _runIfDisabled      Execute method if feature isn't on?
		 */
		protected $_runIfDisabled = false;

		/**
		 * This method is overloaded because of the Command class that was originally defined the
		 * Symfony package.  Inside we set the command name and set the command description.
		 * @return      void
		 */
		protected function configure () {
			// Register the command and set the arguments
			$this->setName ("varnish:purge:all")
			->setDescription ("Purge all cache from varnish servers");
		}

		/**
		 * This method is defined because it is required by the abstract parent class.  It takes in
		 * an input interface and actually does all the work.  It then returns an object that
		 * defines the response that will be displayed in the console.
		 * @param       InputInterface      input               The input interface with the console
		 * @return      Object
		 */
		protected function runCommand ( InputInterface $input ) {
			// Initialize the accounting variables and payload array
			$total = 0;
			$success = 0;
			$payload = [];
			// Ask to purge and iterate over responses
			foreach ( $this->_purger->purgeAll () as $response ) {
				// Log what we are trying to do
				$message = [
					"action" => "purge:all",
					"status" => $response->status,
					"server" => $response->server
				];
				$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
				// Check to see if response was successful
				if ( $response->status == 200 ) {
					// Add success response message
					$serverHtml = "<fg=green>$response->server</>";
					$msg = "successfully purged all cache on $serverHtml";
					array_push ( $payload, $msg );
					$success++;
				}
				else {
					// Otherwise add an error message
					$serverHtml = "<fg=red>$response->server</>";
					$statusHtml = "<fg=red>$response->status</>";
					$msg = "Error Purging all cache on $serverHtml with response code $statusHtml";
					array_push ( $payload, $msg );
				}
				$total++;
			}
			// Return every intermediate result as one
			return [
				"status" => $success > 0 && $total - $success > 0 ? null : $total == $success,
				"message" => "purged all cache from $success/$total varnish servers",
				"payload" => $payload
			];
		}

	}