<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Purge;

	use Magento\Framework\App\Action\Action;
	use Magento\Framework\Controller\ResultFactory;

	/**
	 * Url.php - 
	 * @version         1.0.0
	 * @package         undefined varnish
	 * @category        Purge
	 * @author          Rafael Grigorian - undefined
	 * @license         UNLICENSED
	 */
	class Url extends Action {

 		/**
		 * 
		 * @return
		 */
		public function execute () {
			// Load the purger helper class
			$purger = $this->_objectManager->create ("JetRails\Varnish\Helper\Purger");


			// Extract and validate the url variable from the post request
			$url = $this->getRequest ()->getParam ("url");
			$url = $url == null ? "" : trim ( $url );

			$urlLink = "<a href='$url' >$url</a>";

			// Check to see if there is a url that was passed
			if ( $url !== "" ) {

				

				$handle = curl_init ( $url );
				curl_setopt ( $handle, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, true );
				curl_setopt ( $handle, CURLOPT_AUTOREFERER, true );
				curl_setopt ( $handle, CURLOPT_HEADER, true );
				curl_setopt ( $handle, CURLOPT_CONNECTTIMEOUT, 120 );
				curl_setopt ( $handle, CURLOPT_TIMEOUT, 120 );
				curl_setopt ( $handle, CURLOPT_MAXREDIRS, 10 );
				curl_setopt ( $handle, CURLOPT_CUSTOMREQUEST, "PURGE" );
				curl_setopt ( $handle, CURLOPT_HTTPHEADER, ["JetRails-Host: localhost.com", "JetRails-Url: /test.txt"] );
				$response = curl_exec ( $handle );
				$responseCode = curl_getinfo ( $handle, CURLINFO_HTTP_CODE );
				curl_close ( $handle );

				$urlLink .= $response;

				if ( $responseCode == 200 ) {
					$this->messageManager->addSuccess ("Successfully purged $urlLink");
				}
				else {
					$this->messageManager->addError ("$urlLink responded with error code '$responseCode' when trying to purge");
				}
			}
			else {
				$this->messageManager->addError ("$urlLink is an invalid URL");
			}

			// Redirect back to cache management page
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
        	return $redirect->setPath ("adminhtml/cache/index");
		}

	}