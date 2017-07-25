<?php

	namespace JetRails\Varnish\Console\Command;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Purger;
	use JetRails\Varnish\Logger\Logger;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class AbstractCommand extends Command {
	 
		protected $_data;

		protected $_logger;

		protected $_purger;

		public function __construct ( Data $data, Logger $logger, Purger $purger ) {
			$this->_data = $data;
			$this->_logger = $logger;
			$this->_purger = $purger;
			parent::__construct ();
		}

		protected function execute ( InputInterface $input, OutputInterface $output ) {
			$output->writeln ("");
			$this->runCommand ( $input, $output );
			$output->writeln ("");
		}
	 
	}