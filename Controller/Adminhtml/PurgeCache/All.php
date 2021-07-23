<?php

	namespace JetRails\Varnish\Controller\Adminhtml\PurgeCache;

	use JetRails\Varnish\Controller\Adminhtml\Core\PurgeCache;

	/**
	 * @version         2.0.0
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class All extends PurgeCache {

		protected function _run () {
			foreach ( $this->_purger->all () as $response ) {
				$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), [
					"status" => $response->status,
					"action" => "purge:all",
					"target" => $response->target,
					"server" => $response->server
				]);
				if ( $response->status == 200 ) {
					$targetHtml = "<font color='#79A22E' ><b>$response->target</b></font>";
					$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
					$message = "Purged all cache on $serverHtml";
					array_push ( $this->_successMessages, $message );
				}
				else {
					$targetHtml = "<font color='#E22626' ><b>$response->target</b></font>";
					$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
					$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
					$message = "Failed to purge all cache on $serverHtml with response code $statusHtml";
					array_push ( $this->_warningMessages, $message );
				}
			}
		}

	}
