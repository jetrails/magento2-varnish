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
			$purgeCommand = preg_match ( "/^varnish:purge/", $this->getName () );
			if ( $purgeCommand && !$this->_data->isEnabled () ) {
				$output->writeln (
					"\nCache application must be set to <fg=red>Varnish Cache</>, set it by configuring" .
					" Stores → Advanced → Developer → System → Full Page Cache → Caching Application.\n" . 
					"Alternatively, you can run the <fg=red>varnish:cache:enable</> command.\n"
				);
			}
			else {
				$output->writeln ("");
				$this->runCommand ( $input, $output );
				$output->writeln ("");
			}
		}
	 
	}