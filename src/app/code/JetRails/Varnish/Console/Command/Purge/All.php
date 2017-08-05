<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class All extends AbstractCommand {
	
		protected $_runIfDisabled = false;

	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:purge:all")
	        ->setDescription ("Purge all cache from varnish servers");
	    }
	 
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