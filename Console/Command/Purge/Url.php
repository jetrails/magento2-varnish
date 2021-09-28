<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Input\InputOption;

	/**
	 * @version         2.0.2
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Url extends AbstractCommand {

		protected $_runIfDisabled = false;

		protected function configure () {
			$this
				->setName ("varnish:purge:url")
				->setDescription ("Purge specific url from Varnish Cache™")
				->addArgument ( "url", InputArgument::REQUIRED, "What URL do you want to purge?" );
		}

		protected function runCommand ( InputInterface $input ) {
			$url = $input->getArgument ("url");
			$url = $this->_validator->url ( $url );
			if ( gettype ( $url ) == "object" ) {
				$total = 0;
				$success = 0;
				$payload = [];
				foreach ( $this->_purger->url ( $url ) as $response ) {
					$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), [
						"status" => $response->status,
						"action" => "purge:url",
						"target" => $response->target,
						"server" => $response->server
					]);
					if ( $response->status == 200 ) {
						$targetHtml = "<fg=green>$response->target</>";
						$serverHtml = "<fg=green>$response->server</>";
						$message = "Purged $targetHtml on $serverHtml";
						array_push ( $payload, $message );
						$success++;
					}
					else {
						$targetHtml = "<fg=red>$response->target</>";
						$serverHtml = "<fg=red>$response->server</>";
						$statusHtml = "<fg=red>$response->status</>";
						$message  = "Failed to purge url $targetHtml on $serverHtml ";
						$message .= "with response code $statusHtml";
						array_push ( $payload, $message );
					}
					$total++;
				}
				return [
					"status" => $success > 0 && $total - $success > 0 ? null : $total == $success,
					"message" => "purged url from $success/$total varnish servers",
					"payload" => $payload
				];
			}
			return [ "status" => false, "message" => json_encode ( $url ) ];
		}

	}
