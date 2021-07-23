<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputInterface;

	/**
	 * @version         2.0.0
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class All extends AbstractCommand {

		protected $_runIfDisabled = false;

		protected function configure () {
			$this
				->setName ("varnish:purge:all")
				->setDescription ("Purge all cache from varnish servers");
		}

		protected function runCommand ( InputInterface $input ) {
			$total = 0;
			$success = 0;
			$payload = [];
			foreach ( $this->_purger->all () as $response ) {
				$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), [
					"action" => "purge:all",
					"status" => $response->status,
					"server" => $response->server
				]);
				if ( $response->status == 200 ) {
					$serverHtml = "<fg=green>$response->server</>";
					$msg = "Purged all cache on $serverHtml";
					array_push ( $payload, $msg );
					$success++;
				}
				else {
					$serverHtml = "<fg=red>$response->server</>";
					$statusHtml = "<fg=red>$response->status</>";
					$msg = "Failed to Purge all cache on $serverHtml with response code $statusHtml";
					array_push ( $payload, $msg );
				}
				$total++;
			}
			return [
				"status" => $success > 0 && $total - $success > 0 ? null : $total == $success,
				"message" => "purged all cache from $success/$total varnish servers",
				"payload" => $payload
			];
		}

	}
