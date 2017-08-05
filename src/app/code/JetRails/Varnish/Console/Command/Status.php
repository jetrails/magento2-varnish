<?php

	namespace JetRails\Varnish\Console\Command;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\ArrayInput;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class Status extends AbstractCommand {

	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:status")
	        ->setDescription ("Check to see what caching application is being used");
	    }
	 
	    protected function runCommand ( InputInterface $input ) {
	    	// Check to see if varnish caching is enabled and prepare message
	    	$status = $this->_data->isEnabled ();
	    	$message = $status ? "<options=underscore>Varnish Cache</>" : "<options=underscore>Built-in Cache</>";
	    	$message = "Caching application is set to $message";
	    	// Return the message and status to caller
			return [ "status" => $status, "message" => $message ];
	    }
	 
	}