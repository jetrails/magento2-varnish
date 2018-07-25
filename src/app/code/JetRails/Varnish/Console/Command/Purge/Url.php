<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;

	/**
	 * Url.php - This class inherits from the AbstractCommand.  This command takes in a url and asks
	 * all the saved varnish servers a request to purge the passed url from cache.
	 * @version         1.1.3
	 * @package         JetRails® Varnish
	 * @category        Purge
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Url extends AbstractCommand {

		/**
		 * This internal data member defines if the action should be run if the feature is disabled
		 * in the store config.
		 * @var         Boolean             _runIfDisabled      Execute method if feature isn't on?
		 */
		protected $_runIfDisabled = false;

		/**
		 * This method is overloaded because of the Command class that was originally defined the
		 * Symfony package.  Inside we set the command name and set the command description.  We
		 * also define the arguments that are associated with this command.
		 * @return      void
		 */
		protected function configure () {
			// Register the command and set the arguments
			$this->setName ("varnish:purge:url")
			->setDescription ("Purge specific url from varnish cache")
			->addArgument ( "url", InputArgument::REQUIRED, "What URL do you want to purge?" );
		}

		/**
		 * This method is defined because it is required by the abstract parent class.  It takes in
		 * an input interface and actually does all the work.  It then returns an object that
		 * defines the response that will be displayed in the console.
		 * @param       InputInterface      input               The input interface with the console
		 * @return      Object
		 */
		protected function runCommand ( InputInterface $input ) {
			// Load passed url parameter and validate it
			$url = $input->getArgument ("url");
			$url = $this->_purger->validateUrl ( $url );
			// If an object was returned, then it was a valid url
			if ( gettype ( $url ) == "object" ) {
				// Initialize the accounting variables and payload array
				$total = 0;
				$success = 0;
				$payload = [];
				// Ask to purge and iterate over responses
				foreach ( $this->_purger->purgeUrl ( $url ) as $response ) {
					// Log what we are trying to do
					$message = [
						"status" => $response->status,
						"action" => "purge:url",
						"target" => $response->target,
						"server" => $response->server
					];
					$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
					// Check to see if response was successful
					if ( $response->status == 200 ) {
						// Add success response message
						$targetHtml = "<fg=green>$response->target</>";
						$serverHtml = "<fg=green>$response->server</>";
						$message = "successfully purged url $targetHtml on $serverHtml";
						array_push ( $payload, $message );
						$success++;
					}
					else {
						// Otherwise add an error message
						$targetHtml = "<fg=red>$response->target</>";
						$serverHtml = "<fg=red>$response->server</>";
						$statusHtml = "<fg=red>$response->status</>";
						$message  = "couldn't purge url $targetHtml on $serverHtml ";
						$message .= "with response code $statusHtml";
						array_push ( $payload, $message );
					}
					$total++;
				}
				// Return every intermediate result as one
				return [
					"status" => $success > 0 && $total - $success > 0 ? null : $total == $success,
					"message" => "purged url from $success/$total varnish servers",
					"payload" => $payload
				];
			}
			// Otherwise an error was returned in the form of a string
			return [ "status" => false, "message" => $url ];
		}

	}