<?php

	namespace JetRails\Varnish\Console\Command\Purge;

	use JetRails\Varnish\Console\Command\AbstractCommand;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Input\InputOption;

	/**
	 * @version         3.0.4
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Advanced extends AbstractCommand {

		protected $_runIfDisabled = false;

		protected function configure () {
			$this
				->setName ("varnish:purge:custom")
				->setDescription ("Purge Varnish Cache™ using custom rule")
				->addArgument ( "rule", InputArgument::REQUIRED, "What rule do you want to use to purge?" );
		}

		protected function runCommand ( InputInterface $input ) {
			$rule = $input->getArgument ("rule");
			$rule = $this->_validator->rule ( $rule );
			if ( gettype ( $rule ) == "array" ) {
				$rule = array_pop ( $rule );
				$total = 0;
				$success = 0;
				$payload = [];
				foreach ( $this->_purger->advanced ( $rule ) as $response ) {
					$message = [
						"status" => $response->status,
						"action" => "purge:custom",
						"target" => $response->target,
						"server" => $response->server
					];
					$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), $message );
					if ( $response->status == 200 ) {
						$targetHtml = "<fg=green>$response->target</>";
						$serverHtml = "<fg=green>$response->server</>";
						$message = "Purged using rule $targetHtml on $serverHtml";
						array_push ( $payload, $message );
						$success++;
					}
					else {
						$targetHtml = "<fg=red>$response->target</>";
						$serverHtml = "<fg=red>$response->server</>";
						$statusHtml = "<fg=red>$response->status</>";
						$message  = "Failed to purge with rule $targetHtml on $serverHtml ";
						$message .= "with response code $statusHtml";
						array_push ( $payload, $message );
					}
					$total++;
				}
				return [
					"status" => $success > 0 && $total - $success > 0 ? null : $total == $success,
					"message" => "purged rule from $success/$total varnish servers",
					"payload" => $payload
				];
			}
			return [ "status" => false, "message" => json_encode ( $rule ) ];
		}

	}
