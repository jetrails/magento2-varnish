<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class Store extends AbstractCommand {
	 
		protected $_runIfDisabled = false;

		private function _findStoreViewById ( $storeViews, $id ) {
			foreach ( $storeViews as $storeView ) {
				if ( $storeView->id == $id ) {
					return $storeView;
				}
			}
			return false;
		}

		protected function configure () {
			// Register the command and set the arguments 
			$this->setName ("varnish:purge:store")
			->setDescription ("Purge varnish cache based on store view")
			->addArgument ( "store", InputArgument::OPTIONAL, "Store view id to purge" );
		}
	 
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
							$msg = "Error Purging store view $storeHtml on $serverHtml with response code $statusHtml";
							array_push ( $payload, $msg );
						}
						// Increment the total accounting variable
						$total++;
					}
					// Return every intermediate result as one
					return [
						"status" => $success > 0 && $total - $success > 0 ? null : $total == $success,
						"message" => "purged store view cache from $success/$total varnish servers",
						"payload" => $payload
					];
				}
			}
			// Return an error message
			return [ "status" => false, "message" => "could not find specified store view" ];
		}
	 
	}