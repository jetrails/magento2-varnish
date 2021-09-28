<?php

	namespace JetRails\Varnish\Controller\Adminhtml\PurgeCache;

	use JetRails\Varnish\Controller\Adminhtml\Core\PurgeCache;

	/**
	 * @version         2.0.2
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Advanced extends PurgeCache {

		protected function _run () {
			$rule = $this->getRequest ()->getParam ("rule");
			$rule = $this->_validator->rule ( $rule );
			if ( gettype ( $rule ) == "array" ) {
				$rule = array_pop ( $rule );
				foreach ( $this->_purger->advanced ("$rule") as $response ) {
					$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), [
						"status" => $response->status,
						"action" => "purge:advanced",
						"target" => $response->target,
						"server" => $response->server
					]);
					if ( $response->status == 200 ) {
						$ruleHtml = "<font color='#79A22E' ><b>" . htmlspecialchars ( $rule ) . "</b></font>";
						$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
						$message = "Purged with rule $ruleHtml on $serverHtml";
						array_push ( $this->_successMessages, $message );
					}
					else {
						$ruleHtml = "<font color='#E22626' ><b>" . htmlspecialchars ( $rule ) . "</b></font>";
						$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
						$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
						$message = "Failed to purging with rule $ruleHtml on $serverHtml with response code $statusHtml";
						array_push ( $this->_warningMessages, $message );
					}
				}
			}
			else {
				array_push ( $this->_errorMessages, "$rule" );
			}
		}

	}
