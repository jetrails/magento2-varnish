<?php

	namespace JetRails\Varnish\Helper;

	use Magento\Framework\App\Helper\AbstractHelper;

	/**
	 * @version         3.0.3
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Validator extends AbstractHelper {

		public function url ( $input ) {
			$entry = trim ( $input );
			$entry = preg_replace ( "/^https?:\/\//i", "", $entry );
			$entry = "https://$entry";
			$url = ( object ) [
				"host" => parse_url ( $entry, PHP_URL_HOST ),
				"path" => parse_url ( $entry, PHP_URL_PATH ),
			];
			if ( $url->host == null ) {
				return "Could not parse url from: \"$input\"";
			}
			if ( $url->path == null ) {
				$url->path = "/";
			}
			return $url;
		}

		public function tag ( $input ) {
			$entry = trim ( $input );
			$regexp = "/^[0-9a-z_]+$/i";
			if ( preg_match ( $regexp, $entry ) ) {
				return [ $entry ];
			}
			return "Could not parse valid tag from: \"$input\"";
		}

		public function rule ( $input ) {
			$entry = trim ( $input );
			$regexp = "/^.+$/i";
			if ( preg_match ( $regexp, $entry ) ) {
				return [ $entry ];
			}
			return "Custom rule cannot be empty";
		}

		public function routes ( $entries ) {
			$values = [];
			$errors = [];
			$entries = explode ( "\n", trim ( $entries ) );
			$entries = array_filter ( $entries, function ( $i ) { return trim ( $i ) != ""; } );
			foreach ( $entries as $entry ) {
				$entry = trim ( $entry );
				$regexp = "/^[a-z0-9_-]{3,}(?:\/[a-z0-9_-]{3,}){0,3}$/i";
				if ( preg_match ( $regexp, $entry ) ) {
					$values [] = $entry;
				}
				else {
					$errors [] = "Ignoring invalid exclusion route: <font color='#EB5202' ><b>$entry</b></font>";
				}
			}
			$values = array_filter ( $values, function ( $i ) { return trim ( $i ) != ""; } );
			return (object) [
				"values" => implode ( "\n", $values ),
				"errors" => $errors,
			];
		}

		public function wildcards ( $entries ) {
			$values = [];
			$errors = [];
			$entries = explode ( "\n", trim ( $entries ) );
			$entries = array_filter ( $entries, function ( $i ) { return trim ( $i ) != ""; } );
			foreach ( $entries as $entry ) {
				$entry = trim ( $entry );
				if ( $entry [ 0 ] == "/" ) {
					$regexp = str_replace ( [ "\*\*", "\*" ], [ ".*", "[^\/]*" ], preg_quote ( $entry, "/" ) );
					if ( @preg_match ( "/^$regexp\$/m", null ) !== false ) {
						$values [] = $entry;
						continue;
					}
				}
				$errors [] = "Ignoring invalid wildcard pattern: <font color='#EB5202' ><b>$entry</b></font>";
			}
			$values = array_filter ( $values, function ( $i ) { return trim ( $i ) != ""; } );
			return (object) [
				"values" => implode ( "\n", $values ),
				"errors" => $errors,
			];
		}

		public function regexps ( $entries ) {
			$values = [];
			$errors = [];
			$entries = explode ( "\n", trim ( $entries ) );
			$entries = array_filter ( $entries, function ( $i ) { return trim ( $i ) != ""; } );
			foreach ( $entries as $entry ) {
				$entry = trim ( $entry );
				if ( @preg_match ( $entry, null ) === false ) {
					$errors [] = "Ignoring invalid regular expression pattern: <font color='#EB5202' ><b>$entry</b></font>";
				}
				else {
					$values [] = $entry;
				}
			}
			$values = array_filter ( $values, function ( $i ) { return trim ( $i ) != ""; } );
			return (object) [
				"values" => implode ( "\n", $values ),
				"errors" => $errors,
			];
		}

	}
