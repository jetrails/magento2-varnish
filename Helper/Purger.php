<?php

	namespace JetRails\Varnish\Helper;

	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\Helper\AbstractHelper;

	/**
	 * @version         3.0.0
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Purger extends AbstractHelper {

		protected $_data;

		public function __construct (
			Data $data
		) {
			$this->_data = $data;
		}

		private function _purge ( $target, $additionalHeaders = [] ) {
			$responses = [];
			foreach ( $this->_data->getVarnishServersWithPorts () as $server ) {
				$handle = curl_init ( $server->host . ":" . $server->port );
				curl_setopt ( $handle, CURLOPT_PORT, $server->port );
				curl_setopt ( $handle, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, true );
				curl_setopt ( $handle, CURLOPT_AUTOREFERER, true );
				curl_setopt ( $handle, CURLOPT_HEADER, true );
				curl_setopt ( $handle, CURLOPT_CONNECTTIMEOUT, 10 );
				curl_setopt ( $handle, CURLOPT_TIMEOUT, 10 );
				curl_setopt ( $handle, CURLOPT_MAXREDIRS, 3 );
				curl_setopt ( $handle, CURLOPT_CUSTOMREQUEST, "PURGE" );
				curl_setopt ( $handle, CURLOPT_HTTPHEADER, $additionalHeaders );
				$response = curl_exec ( $handle );
				$responseCode = curl_getinfo ( $handle, CURLINFO_HTTP_CODE );
				curl_close ( $handle );
				array_push ( $responses, ( object ) [
					"server"    => $server->host . ":" . $server->port,
					"target"    => $target,
					"status"    => $responseCode
				]);
			}
			return $responses;
		}

		public function tag ( $tag ) {
			$pattern = preg_quote ( $tag, "/" );
			$pattern = "(,|^)$pattern(,|$)";
			$expression = "obj.http.X-Magento-Tags ~ $pattern";
			return $this->_purge ( "$tag", [ "JR-Purge: $expression" ]);
		}
		
		public function url ( $url ) {
			$escaped = preg_quote ( trim ( $url->path, "/" ), "/" );
			$escaped = "^\/*$escaped\/?(?:\?.*)?$";			
			$pathExp = "req.url ~ $escaped";
			$hostExp = "req.http.host == $url->host";
			$expression = "$hostExp && $pathExp";
			return $this->_purge ( $url->host . $url->path, [ "JR-Purge: $expression" ] );
		}

		public function all () {
			$additionalHeaders = [ "JR-Purge: req.http.host ~ .*" ];
			return $this->_purge ( "Everything", $additionalHeaders );
		}

		public function advanced ( $rule ) {
			return $this->_purge ( "$rule", [ "JR-Purge: $rule" ]);
		}

	}
