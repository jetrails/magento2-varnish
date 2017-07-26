<?php

	namespace JetRails\Varnish\Console\Command\Status;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Magento\PageCache\Model\Config;
	use Symfony\Component\Console\Input\ArrayInput;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class Set extends AbstractCommand {

	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:status:set")
	        ->setDescription ("Check to see if what caching application is being used")
	        ->addArgument ( "state", InputArgument::REQUIRED, "Should we enable varnish cache?" );
	    }
	 
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