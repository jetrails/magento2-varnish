<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class All extends AbstractCommand {
	
	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:purge:all")
	        ->setDescription ("Purge all cache from Varnish servers");
	    }
	 
	    protected function runCommand ( InputInterface $input, OutputInterface $output ) {
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
					$msg = "Successfully purged all cache on $serverHtml";
					$output->writeln ( $msg );
				}
				else {
					// Otherwise add an error message
					$serverHtml = "<fg=red>$response->server</>";
					$statusHtml = "<fg=red>$response->status</>";
					$msg = "Error Purging all cache on $serverHtml with response code $statusHtml";
					$output->writeln ( $msg );
				}
			}
	    }
	 
	}