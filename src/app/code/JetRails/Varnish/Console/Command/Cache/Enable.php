<?php

	namespace JetRails\Varnish\Console\Command\Cache;

	use JetRails\Varnish\Helper\Data;
	use Magento\PageCache\Model\Config;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\ArrayInput;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class Enable extends Command {
	 
		protected $_data;

		public function __construct ( Data $data ) {
			$this->_data = $data;
			parent::__construct ();
		}

	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:cache:enable")
	        ->setDescription ("Set caching application to varnish cache");
	    }
	 
	    protected function execute ( InputInterface $input, OutputInterface $output ) {
	    	$this->_data->setCachingApplication ( Config::VARNISH );
			// Run the status command
			$arrayInput = new ArrayInput ([ "command" => "varnish:cache:status" ]);
			$command = $this->getApplication ()->find ("varnish:cache:status");
		    $command->run ( $arrayInput, $output );
	    }
	 
	}