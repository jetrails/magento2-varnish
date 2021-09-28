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
	class Tag extends PurgeCache {

		protected function _run () {
			$tag = $this->getRequest ()->getParam ("tag");
			$tag = $this->_validator->tag ( $tag );
			if ( gettype ( $tag ) == "array" ) {
				$tag = array_pop ( $tag );
				foreach ( $this->_purger->tag ( $tag ) as $response ) {
					$this->_logger->blame ( $this->_data->getLoggedInUserInfo (), [
						"status" => $response->status,
						"action" => "purge:tag",
						"target" => $response->target,
						"server" => $response->server
					]);
					if ( $response->status == 200 ) {
						$tagHtml = "<font color='#79A22E' ><b>$tag</b></font>";
						$serverHtml = "<font color='#79A22E' ><b>$response->server</b></font>";
						$message = "Purged with tag $tagHtml on $serverHtml";
						array_push ( $this->_successMessages, $message );
					}
					else {
						$tagHtml = "<font color='#E22626' ><b>$tag</b></font>";
						$serverHtml = "<font color='#E22626' ><b>$response->server</b></font>";
						$statusHtml = "<font color='#E22626' ><b>$response->status</b></font>";
						$message = "Failed to purge with tag $tagHtml on $serverHtml with response code $statusHtml";
						array_push ( $this->_warningMessages, $message );
					}
				}
			}
			else {
				array_push ( $this->_errorMessages, "$tag" );
			}
		}

	}
