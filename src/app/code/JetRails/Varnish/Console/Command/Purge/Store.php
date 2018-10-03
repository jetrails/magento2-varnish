<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;

	/**
	 * Store.php - This class inherits from the AbstractCommand.  This command takes in a store view
	 * id and takes the base url from that store view and clears all urls from all the varnish cache
	 * servers that start with the store view's base url.  If no argument is passed, then a list of
	 * store views along with their base url, id, and name is displayed in the payload of the
	 * response.
	 * @version         1.1.6
	 * @package         JetRails® Varnish
	 * @category        Purge
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Store extends AbstractCommand {

		/**
		 * This internal data member defines if the action should be run if the feature is disabled
		 * in the store config.
		 * @var         Boolean             _runIfDisabled      Execute method if feature isn't on?
		 */
		protected $_runIfDisabled = false;

		/**
		 * This private method is used to find the store view information by the store view id.  If
		 * the store view id exists then the object with more store view information is returned,
		 * otherwise false is returned.
		 * @param       Array               storeViews          An array of store view information
		 * @param       Integer             id                  The store view id we are looking for
		 */
		private function _findStoreViewById ( $storeViews, $id ) {
			foreach ( $storeViews as $storeView ) {
				if ( $storeView->id == $id ) {
					return $storeView;
				}
			}
			return false;
		}

		/**
		 * This method is overloaded because of the Command class that was originally defined the
		 * Symfony package.  Inside we set the command name and set the command description.  We
		 * also define the arguments that are associated with this command.
		 * @return      void
		 */
		protected function configure () {
			// Register the command and set the arguments
			$this->setName ("varnish:purge:store")
			->setDescription ("Purge varnish cache based on store view")
			->addArgument ( "store", InputArgument::OPTIONAL, "Store view id to purge" );
		}

		/**
		 * This method is defined because it is required by the abstract parent class.  It takes in
		 * an input interface and actually does all the work.  It then returns an object that
		 * defines the response that will be displayed in the console.
		 * @param       InputInterface      input               The input interface with the console
		 * @return      Object
		 */
		protected function runCommand ( InputInterface $input ) {
			$store = $input->getArgument ("store");
			$storeViews = $this->_data->getStoreViews ();
			if ( !$store ) {
				$payload = [ sprintf (
					"<options=bold>%-16s</><options=bold>%-35s</> <options=bold>%-35s</>",
					"ID",
					"Store View Name",
					"Store View Base Url"
				)];
				foreach ( $storeViews as $storeView ) {
					$msg = sprintf (
						"<fg=green>%-16s</>%-35s %-35s",
						$storeView->id,
						$storeView->name,
						$storeView->url
					);
					array_push ( $payload, $msg );
				}
				return [
					"status" => false,
					"message" => "please pass store view id as parameter, store views are below:",
					"payload" => $payload
				];
			}
			else if ( $storeView = $this->_findStoreViewById ( $storeViews, $store ) ) {
				// Make sure store id is valid
				$url = $this->_purger->validateAndResolveStoreId ( $store );
				if ( gettype ( $url ) == "object" ) {
					// Initialize the accounting variables and payload array
					$total = 0;
					$success = 0;
					$payload = [];
					// Ask to purge and iterate over responses
					foreach ( $this->_purger->purgeStore ( $url ) as $response ) {
						// Log what we are trying to do
						$message = [
							"status" => $response->status,
							"action" => "purge:store",
							"target" => $response->target,
							"server" => $response->server
						];
						$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
						// Check to see if response was successful
						if ( $response->status == 200 ) {
							// Add success response message
							$storeHtml = "<fg=green>$response->target</>";
							$serverHtml = "<fg=green>$response->server</>";
							$msg = "Successfully purged store view $storeHtml on $serverHtml";
							array_push ( $payload, $msg );
							$success++;
						}
						else {
							// Otherwise add an error message
							$storeHtml = "<fg=red>$response->target</>";
							$serverHtml = "<fg=red>$response->server</>";
							$statusHtml = "<fg=red>$response->status</>";
							$msg = "Error Purging store view $storeHtml on";
							$msg = "$msg $serverHtml with response code $statusHtml";
							array_push ( $payload, $msg );
						}
						// Increment the total accounting variable
						$total++;
					}
					// Return every intermediate result as one
					$status = $success > 0 && $total - $success > 0 ? null : $total == $success;
					$message = "purged store view cache from $success/$total varnish servers";
					return [ "status" => $status, "message" => $message, "payload" => $payload ];
				}
			}
			// Return an error message
			return [ "status" => false, "message" => "could not find specified store view" ];
		}

	}
