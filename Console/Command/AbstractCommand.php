<?php

	namespace JetRails\Varnish\Console\Command;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Helper\Validator;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\App\Cache\TypeListInterface;
	use Magento\Framework\Console\Cli;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	/**
	 * @version         3.0.5
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	abstract class AbstractCommand extends Command {

		protected $_cacheTypeList;
		protected $_data;
		protected $_validator;
		protected $_logger;
		protected $_purger;
		protected $_runIfDisabled = true;

		public function __construct (
			Data $data,
			Validator $validator,
			Logger $logger,
			Purger $purger,
			TypeListInterface $cacheTypeList
		) {
			parent::__construct ();
			$this->_cacheTypeList = $cacheTypeList;
			$this->_data = $data;
			$this->_validator = $validator;
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
			$output->writeln ("");
			$this->_printLabel ( $output, "Powered By", "The JetRails Team" );
			$this->_printLabel ( $output, "Email Us", "support@jetrails.com" );
			$this->_printLabel ( $output, "Call Us", "+1 (888) 997-2457" );
			$this->_printLabel ( $output, "Disclaimer", "Varnish is a registered trademark of Varnish Software AB and its affiliates." );
			$output->writeln ("");
			if ( !$this->_runIfDisabled && !$this->_data->isEnabled () ) {
				$status   = "unsuccessful";
				$message  = "Cache application must be set to <options=underscore>Varnish Cache™</>";
				$payload  = "<fg=red>varnish:status:set enable</>";
				$payload .= "Run '$payload' to set Varnish Cache™ as caching application";
				$response = ( object ) [
					"status" => $status,
					"message" => $message,
					"payload" => [ $payload ]
				];
			}
			else {
				$response = ( object ) $this->runCommand ( $input );
				$status = "mixed";
				if ( $response->status === true ) $status = "successful";
				if ( $response->status === false ) $status = "unsuccessful";
				$response->status = $status;
			}
			$parameters = $input->getArguments ();
			unset ( $parameters ["command"] );
			$parametersKeys = array_keys ( $parameters );
			$parametersKeys = array_map ( function ( $i ) use ( $parameters ) {
				return "$i → " . ( $parameters [ $i ] == "" ? "null" : $parameters [ $i ] );
			}, $parametersKeys );
			$parameter = $parametersKeys;
			$parameters = count ( $parameters ) == 0 ? "none" : implode ( ", ", $parameter );
			$this->_printLine ( $output, "Command", $this->getName () );
			$this->_printLine ( $output, "Parameters", $parameters );
			$this->_printLine ( $output, "Status", $response->status );
			$this->_printLine ( $output, "Response", $response->message );
			if ( property_exists ( $response , "payload" ) && count ( $response->payload ) > 0 ) {
				$output->writeln ( "\n" . implode ( "\n", $response->payload ) );
			}
			$output->writeln ("");
			return Cli::RETURN_SUCCESS;
		}

		protected abstract function runCommand ( InputInterface $input );

	}
