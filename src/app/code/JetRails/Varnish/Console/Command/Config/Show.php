<?php

	namespace JetRails\Varnish\Console\Command\Config;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;

	class Show extends AbstractCommand {
	
	    protected function configure () {
	    	// Register the command and set the arguments 
	        $this->setName ("varnish:config:show")
	        ->setDescription ("Show configuration settings");
	    }
	 
	    protected function runCommand ( InputInterface $input, OutputInterface $output ) {
			$output->writeln ( "<options=bold>Debug Mode</>:\t\t\t" . $this->_data->isDebugMode () );
			$output->writeln ( "<options=bold>Backend Server</>:\t\t\t" . ( $this->_data->getBackendServer () == "" ? "\"\"" : $this->_data->getBackendServer ()->host . ":" . $this->_data->getBackendServer ()->port ) );
			$output->writeln ( "<options=bold>Varnish Servers</>:\t\t" . implode ( "\n\t\t\t\t", array_map ( function ( $server ) {
				return $server->host . ":" . $server->port;
			}, $this->_data->getVarnishServersWithPorts () ) ) );
			$output->writeln ( "<options=bold>Purge After Product Save</>:\t" . $this->_data->shouldPurgeAfterProductSave () );
			$output->writeln ( "<options=bold>Purge After CMS Page Save</>:\t" . $this->_data->shouldPurgeAfterCmsPageSave () );
			$output->writeln ( "<options=bold>Excluded Urls</>:\t\t\t" . implode ( "\n\t\t\t\t", $this->_data->getExcludedUrls () ) );
			$output->writeln ( "<options=bold>Excluded Routes</>:\t\t" . implode ( "\n\t\t\t\t", $this->_data->getExcludedRoutes () ) );
	    }
	 

	}