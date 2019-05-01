<?php

	namespace JetRails\Varnish\Console\Command;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Magento\Framework\App\Cache\TypeListInterface;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	/**
	 * AbstractCommand.php - This class is abstract and inherits from the Symfony command class.  It
	 * is meant to be a buffer between that class and the commands that are implemented in this
	 * module.  Instead of using the execute command, the child classes overload the runCommand
	 * method.
	 * @version         1.1.9
	 * @package         JetRails® Varnish
	 * @category        Status
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	abstract class AbstractCommand extends Command {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.  Also a boolean variable is included
		 * that defines if the action should be run if the feature is disabled in the store config.
		 * @var         TypeListInterface   _cacheTypeList      Instance of the TypeListInterface class
		 * @var         Data                _data               Instance of the Data class
		 * @var         Logger              _logger             Instance of the Logger class
		 * @var         Purger              _purger             Instance of the Purger class
		 * @var         Boolean             _runIfDisabled      Execute method if feature isn't on?
		 */
		protected $_cacheTypeList;
		protected $_data;
		protected $_logger;
		protected $_purger;
		protected $_runIfDisabled = true;


		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       Data                data                Instance of the Data class
		 * @param       Logger              logger              Instance of the Logger class
		 * @param       Purger              purger              Instance of the Purger class
		 * @param       TypeListInterface   cacheTypeList       Instance of the TypeListInterface class
		 */
		public function __construct (
			Data $data,
			Logger $logger,
			Purger $purger,
			TypeListInterface $cacheTypeList
		) {
			// Call the parent constructor
			parent::__construct ();
			// Save injected classes internally
			$this->_cacheTypeList = $cacheTypeList;
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_purger = $purger;
		}

		/**
		 * This method takes in an output interface and a label and value string.  It then simply
		 * formats the label and value uniformly and outputs it to the output interface.
		 * @param       OutputInterface     output              Interface to write to console
		 * @param       String              label               label value to write (LHS)
		 * @param       String              value               label value to write (RHS)
		 * @return      void
		 */
		private function _printLabel ( $output, $label, $value ) {
			// Construct the message and write it to the console
			$message = sprintf ( "<fg=white>%-16s%s</>", "$label:", $value );
			$output->writeln ( $message );
		}

		/**
		 * This method takes in an output interface and a label and value string.  It then simply
		 * formats the label and value uniformly and outputs it to the output interface.
		 * @param       OutputInterface     output              Interface to write to console
		 * @param       String              label               label value to write (LHS)
		 * @param       String              value               label value to write (RHS)
		 * @return      void
		 */
		private function _printLine ( $output, $label, $value ) {
			// Construct the message and write it to the console
			$message = sprintf ( "<fg=cyan>%-16s</>%s", "$label:", $value );
			$output->writeln ( $message );
		}

		/**
		 * This method is here because it interfaces with the abstract parent class.  It takes in an
		 * input and output interface and it runs the command.  We make this method protected and
		 * ask the child class that inherits from this class to use the runCommand method.  This
		 * gives us more control with the output interface.
		 * @param       InputInterface      input               The input interface
		 * @param       OutputInterface     output              The output interface
		 * @return      void
		 */
		protected function execute ( InputInterface $input, OutputInterface $output ) {
			// Print out the header message
			$output->writeln ("");
			$this->_printLabel ( $output, "Powered By", "The JetRails Team" );
			$this->_printLabel ( $output, "Email Us", "support@jetrails.com" );
			$this->_printLabel ( $output, "Call Us", "+1 (888) 554-9990" );
			$this->_printLabel ( $output, "Disclaimer", "Varnish is a registered trademark of Varnish Software AB and its affiliates." );
			$output->writeln ("");
			// Check to see if we should run the command if feature is disabled
			if ( !$this->_runIfDisabled && !$this->_data->isEnabled () ) {
				// Define the return parameters
				$status   = "unsuccessful";
				$message  = "Cache application must be set to <options=underscore>Varnish Cache™</>";
				$payload  = "<fg=red>varnish:status:set enable</>";
				$payload .= "Run '$payload' to set Varnish Cache™ as caching application";
				// Set the response object to be the above parameters
				$response = ( object ) [
					"status" => $status,
					"message" => $message,
					"payload" => [ $payload ]
				];
			}
			else {
				// Run the command and reformat the status of the response
				$response = ( object ) $this->runCommand ( $input );
				$status = "mixed";
				if ( $response->status === true ) $status = "successful";
				if ( $response->status === false ) $status = "unsuccessful";
				$response->status = $status;
			}
			// Format the parameters
			$parameters = $input->getArguments ();
			unset ( $parameters ["command"] );
			$parametersKeys = array_keys ( $parameters );
			$parametersKeys = array_map ( function ( $i ) use ( $parameters ) {
				return "$i → " . ( $parameters [ $i ] == "" ? "null" : $parameters [ $i ] );
			}, $parametersKeys );
			$parameter = $parametersKeys;
			$parameters = count ( $parameters ) == 0 ? "none" : implode ( ", ", $parameter );
			// Print out results
			$this->_printLine ( $output, "Command", $this->getName () );
			$this->_printLine ( $output, "Parameters", $parameters );
			$this->_printLine ( $output, "Status", $response->status );
			$this->_printLine ( $output, "Response", $response->message );
			// If there is a payload, then print it out
			if ( property_exists ( $response , "payload" ) && count ( $response->payload ) > 0 ) {
				$output->writeln ( "\n" . implode ( "\n", $response->payload ) );
			}
			$output->writeln ("");
		}

		/**
		 * This is the abstract method that we require the child class to be overloaded. This class
		 * only gives access to the input interface and require the child class to return an object
		 * consisting of the following:
		 *
		 * status:              true for success, false for unsuccessful, null for mixed
		 * message:             string value for response label in header
		 * payload (optional):  an array of strings, will be displayed below the header labels
		 *
		 * @param       InputInterface      Input               The input interface containing args
		 * @return      object                                  Contents described above
		 */
		protected abstract function runCommand ( InputInterface $input );

	}
