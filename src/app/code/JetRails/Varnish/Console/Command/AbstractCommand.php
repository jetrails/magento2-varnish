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

		public function __construct ( Data $data, Logger $logger, Purger $purger ) {
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_purger = $purger;
			parent::__construct ();
		}

		private function _printLabel ( $output, $label, $value ) {
			$message = sprintf ( "<fg=cyan>%-16s%s</>", "$label", $value );
			$output->writeln ( $message );
		}

		private function _printLine ( $output, $label, $value ) {
			$message = sprintf ( "<fg=yellow>%-16s</>%s", "$label", $value );
			$output->writeln ( $message );
		}

		protected function execute ( InputInterface $input, OutputInterface $output ) {
			$output->writeln ("");
			$this->_printLabel ( $output, "Powered By", "The JetRails Team" );
			$this->_printLabel ( $output, "Email Us", "support@jetrails.com" );
			$this->_printLabel ( $output, "Call Us", "+1 (888) 554-9990" );
			$output->writeln ("");

			$purgeCommand = preg_match ( "/^varnish:purge/", $this->getName () );

			if ( $purgeCommand && !$this->_data->isEnabled () ) {
				$output->writeln (
					"Cache application must be set to <options=underscore>Varnish Cache</>\n" .
					"<fg=yellow>Backend</>: Stores → Advanced → Developer → System → Full Page Cache → Caching Application\n" . 
					"<fg=yellow>Console</>: varnish:status:set enable\n"
				);
			}
			else {

				$response = ( object ) $this->runCommand ( $input );

				$status = $response->status ? "successful" : ( $response->status === null ? "mixed" : "unsuccessful" );
				$parameters = $input->getArguments ();
				unset ( $parameters ["command"] );
				$parametersKeys = array_keys ( $parameters );
				$parametersKeys = array_map ( function ( $i ) use ( $parameters ) { return "$i → " . $parameters [ $i ]; }, $parametersKeys );
				$parameter = $parametersKeys;
				$parameters = count ( $parameters ) == 0 ? "none" : implode ( ", ", $parameter );

				$this->_printLine ( $output, "Command", $this->getName () );
				$this->_printLine ( $output, "Parameters", $parameters );
				$this->_printLine ( $output, "Status", $status );
				$this->_printLine ( $output, "Response", $response->message );
				if ( isset ( $response->payload ) ) $output->writeln ( "\n" . implode ( "\n", $response->payload ) );
				$output->writeln ("");
			}
		}

		protected abstract function runCommand ( InputInterface $input );
	 
	}