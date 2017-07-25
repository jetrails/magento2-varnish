<?php

	namespace JetRails\Varnish\Console\Command\Cache;

	use JetRails\Varnish\Helper\Data;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\ArrayInput;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\NullOutput;
	use Symfony\Component\Console\Output\OutputInterface;

	class Status extends Command {
	 
		protected $_data;

		public function __construct ( Data $data ) {
			$this->_data = $data;
			parent::__construct ();
		}

	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:cache:status")
	        ->setDescription ("Check to see if what caching application is being used");
	    }
	 
	    protected function execute ( InputInterface $input, OutputInterface $output ) {
	    	// Check to see if varnish caching is enabled
	    	$status = $this->_data->isEnabled ();
	    	$type = $status ? "<fg=green>Varnish Cache</>" : "<fg=red>Built-in Cache</>";
			$output->writeln ("Caching application is set to $type");
	    }
	 
	}