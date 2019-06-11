<?php

	namespace JetRails\Varnish\Console\Command\Status;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Magento\Framework\App\Cache\Type\Config as ConfigType;
	use Magento\PageCache\Model\Config;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;

	/**
	 * Set.php - This class inherits from the AbstractCommand.  This command takes in an additional
	 * argument, either enable or disable, and sets the caching application to 'Varnish Cache' or
	 * 'Built-in Cache' respectfully.
	 * @version         1.1.9
	 * @package         JetRails® Varnish
	 * @category        Status
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Set extends AbstractCommand {

		/**
		 * This method is overloaded because of the Command class that was originally defined the
		 * Symfony package.  Inside we set the command name and set the command description.  We
		 * also define the arguments that are associated with this command.
		 * @return      void
		 */
		protected function configure () {
			// Register the command and set the arguments
			$this->setName ("varnish:status:set")
			->setDescription ("Set what caching application is being used")
			->addArgument ( "state", InputArgument::REQUIRED, "Should we enable Varnish Cache™?" );
		}

		/**
		 * This method is defined because it is required by the abstract parent class.  It takes in
		 * an input interface and actually does all the work.  It then returns an object that
		 * defines the response that will be displayed in the console.
		 * @param       InputInterface      input               The input interface with the console
		 * @return      Object
		 */
		protected function runCommand ( InputInterface $input ) {
			// Get the desired state and current state
			$state = $input->getArgument ("state");
			$current = $this->_data->isEnabled ();
			// Check to see if the passed argument is valid
			if ( $state == "enable" || $state == "disable" ) {
				// Convert state to boolean
				$state = $state == "enable";
				// If the desired state is different then the current one
				if ( $state != $current ) {
					// Set the state value in the store config
					$stateValue = $state ? Config::VARNISH : Config::BUILT_IN;
					$this->_data->setCachingApplication ( $stateValue );
					$this->_data->setEnable ( $stateValue === Config::VARNISH );
					// Clean the config cache so we get the right values when querying for them
					$this->_cacheTypeList->cleanType ( ConfigType::TYPE_IDENTIFIER );
					// Construct the message and respond to caller
					$message = $state ? "Varnish" : "Built-in";
					$message = "<options=underscore>" . $message . " Cache</>";
					$message = "Caching application is now set to " . $message;
					return [ "status" => true, "message" => $message ];
				}
				// Respond to caller that the desired argument is already in place
				$message = "Requested state already in place";
				return [ "status" => false, "message" => $message ];
			}
			// Respond to caller stating what arguments are valid
			$message = "Invalid argument, pass either enable or disable as an argument";
			return [ "status" => false, "message" => $message ];
		}

	}
