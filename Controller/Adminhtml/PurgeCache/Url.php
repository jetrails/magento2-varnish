<?php

	namespace JetRails\Varnish\Controller\Adminhtml\PurgeCache;

	use JetRails\Varnish\Controller\Adminhtml\Core\PurgeCache;

	/**
	 * @version         3.0.4
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Url extends PurgeCache {

		protected function _run () {
			$url = $this->getRequest ()->getParam ("url");
			$url = $this->_validator->url ( $url );
			if ( gettype ( $url ) == "object" ) {
				$responses = $this->_purger->url ( $url );
				foreach ( $responses as $response ) {
					$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), [
						"status" => $response->status,
						"action" => "purge:url",
						"target" => $response->target,
						"server" => $response->server
					]);
					if ( $response->status == 200 ) {
						$targetHtml = "<font color='#79A22E' ><b>$response->target</b></font>";
						$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
						$message = "Purged url $targetHtml on $serverHtml";
						array_push ( $this->_successMessages, $message );
					}
					else {
						$targetHtml = "<font color='#E22626' ><b>$response->target</b></font>";
						$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
						$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
						$message = "Failed to purge url $targetHtml on $serverHtml with response code $statusHtml";
						array_push ( $this->_warningMessages, $message );
					}
				}
			}
			else {
				array_push ( $this->_errorMessages, "$url" );
			}
		}

	}
