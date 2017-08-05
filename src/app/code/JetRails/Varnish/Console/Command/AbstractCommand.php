<?php

	namespace JetRails\Varnish\Console\Command;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	abstract class AbstractCommand extends Command {
	 
		protected $_data;

		protected $_logger;

		protected $_purger;

		protected $_runIfDisabled = true;

		public function __construct ( Data $data, Logger $logger, Purger $purger ) {
			parent::__construct ();
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_purger = $purger;
		}

		private function _printLabel ( $output, $label, $value ) {
			$message = sprintf ( "<fg=white>%-16s%s</>", "$label:", $value );
			$output->writeln ( $message );
		}

		private function _printLine ( $output, $label, $value ) {
			$message = sprintf ( "<fg=cyan>%-16s</>%s", "$label:", $value );
			$output->writeln ( $message );
		}

		protected function execute ( InputInterface $input, OutputInterface $output ) {
			// Print out the header message
			$output->writeln ("");
			$this->_printLabel ( $output, "Powered By", "The JetRails Team" );
			$this->_printLabel ( $output, "Email Us", "support@jetrails.com" );
			$this->_printLabel ( $output, "Call Us", "+1 (888) 554-9990" );
			$output->writeln ("");
			// Check to see if we should run the command if feature is disabled
			if ( !$this->_runIfDisabled && !$this->_data->isEnabled () ) {
				$response = ( object ) [
					"status" => "unsuccessful",
					"message" => "Cache application must be set to <options=underscore>Varnish Cache</>",
					"payload" => [ "Run '<fg=red>varnish:status:set enable</>' to set varnish cache as caching application" ]
				];
			}
			else {
				// Run the command and reformat the status of the response
				$response = ( object ) $this->runCommand ( $input );
				$response->status = $response->status ? "successful" : ( $response->status === null ? "mixed" : "unsuccessful" );
			}
			// Format the parameters
			$parameters = $input->getArguments ();
			unset ( $parameters ["command"] );
			$parametersKeys = array_keys ( $parameters );
			$parametersKeys = array_map ( function ( $i ) use ( $parameters ) { return "$i → " . ( $parameters [ $i ] == "" ? "null" : $parameters [ $i ] ); }, $parametersKeys );
			$parameter = $parametersKeys;
			$parameters = count ( $parameters ) == 0 ? "none" : implode ( ", ", $parameter );
			// Print out results
			$this->_printLine ( $output, "Command", $this->getName () );
			$this->_printLine ( $output, "Parameters", $parameters );
			$this->_printLine ( $output, "Status", $response->status );
			$this->_printLine ( $output, "Response", $response->message );
			// If there is a payload, then print it out
			if ( property_exists ( $response , "payload" ) && count ( $response->payload ) > 0 ) $output->writeln ( "\n" . implode ( "\n", $response->payload ) );
			$output->writeln ("");
		}

		protected abstract function runCommand ( InputInterface $input );
	 
	}