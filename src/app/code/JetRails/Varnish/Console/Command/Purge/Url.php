<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class Url extends AbstractCommand {

	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:purge:url")
	        ->setDescription ("Purge specific url from Varnish cache")
	        ->addArgument ( "url", InputArgument::REQUIRED, "What URL do you want to purge?" );
	    }
	 
	    protected function runCommand ( InputInterface $input, OutputInterface $output ) {
			// Load passed url parameter and validate it
			$url = $input->getArgument ("url");
			$url = $this->_purger->validateUrl ( $url );
			// If an object was returned, then it was a valid url
			if ( gettype ( $url ) == "object" ) {
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
						$message = "Successfully purged url $targetHtml on $serverHtml";
						$output->writeln ( $message );
					}
					else {
						// Otherwise add an error message
						$targetHtml = "<fg=red>$response->target</>";
						$serverHtml = "<fg=red>$response->server</>";
						$statusHtml = "<fg=red>$response->status</>";
						$message = "Error purging url $targetHtml on $serverHtml with response code $statusHtml";
						$output->writeln ( $message );
					}
				}
			}
			// Otherwise an error was returned in the form of a string
			else { $output->writeln ( "<fg=red>" . $url . "</>" ); }
	    }
	 
	}