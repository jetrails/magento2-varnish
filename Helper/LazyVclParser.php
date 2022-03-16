<?php

	namespace JetRails\Varnish\Helper;

	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\App\Helper\AbstractHelper;

	/**
	 * @version         3.0.1
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class LazyVclParser extends AbstractHelper {

		private $data = "";
		private $cursor = 0;

		function setData ( $data ) {
			$this->data = $data;
			$this->cursor = 0;
		}

		function advanceWithToken ( $string, $token ) {
			$this->cursor += strlen ( $string );
			return $token;
		}

		function extractBody ( $value ) {
			$output = "";
			$opened = 1;
			while ( strlen ( $value ) > 0 ) {
				if ( $opened <= 0 ) break;
				$current = "";
				switch ( true ) {
					case preg_match ("/^[\r\n\t ]+/s", $value, $match):
					case preg_match ("/^#([^\r\n]*)\n/s", $value, $match):
					case preg_match ("/^\/\*(.*?)\*\//s", $value, $match):
					case preg_match ("/^\"(?:\\\.|(?!\").)*+\"/s", $value, $match):
					case preg_match ("/^'(?:\\\.|(?!').)*+'/s", $value, $match):
						$current = $match [0];
						break;
					case preg_match ("/^{/s", $value, $match):
						$current = "{";
						$opened++;
						break;
					case preg_match ("/^}/s", $value, $match):
						$current = "}";
						$opened--;
						break;
					default:
						$current = $value [0];
				}
				$output .= $current;
				$value = substr ( $value, strlen ( $current ) );
			}
			return $opened === 0 ? substr ( $output, 0, -1 ) : $output;
		}

		function nextToken () {
			$value = substr ( $this->data, $this->cursor );
			if ( strlen ( $value ) === 0 ) {
				return $this->advanceWithToken ( "", [
					"type" => "eot",
					"raw" => "",
				]);
			}
			switch ( true ) {
				case preg_match ("/^[\r\n\t ]+/s", $value, $match):
					return $this->advanceWithToken ( $match [0], [
						"type" => "whitespace",
						"raw" => $match [0],
						"value" => $match [0],
					]);break;
				case preg_match ("/^\/\*(.*?)\*\//s", $value, $match):
					return $this->advanceWithToken ( $match [0], [
						"type" => "multi-comment",
						"raw" => $match [0],
						"value" => $match [1],
					]);
				case preg_match ("/^#([^\r\n]*)\n/s", $value, $match):
					return $this->advanceWithToken ( $match [0], [
						"type" => "single-comment",
						"raw" => $match [0],
						"value" => $match [1],
					]);
				case preg_match ("/^vcl[\t ]+([0-9]+(?:\.[0-9]+)?)[\t ]*;[\t ]*\n/s", $value, $match):
					return $this->advanceWithToken ( $match [0], [
						"type" => "vcl",
						"raw" => $match [0],
						"value" => $match [1],
					]);
				case preg_match ("/^import[\t ]+([^; \t]+)[\t ]*;[\t ]*\n/s", $value, $match):
					return $this->advanceWithToken ( $match [0], [
						"type" => "import",
						"raw" => $match [0],
						"value" => $match [1],
					]);
				case preg_match ("/^include[\t ]+([^; \t]+)[\t ]*;[\t ]*\n/s", $value, $match):
					return $this->advanceWithToken ( $match [0], [
						"type" => "include",
						"raw" => $match [0],
						"value" => $match [1],
					]);
				case preg_match ("/^(backend|acl|sub)[\t ]+([^ \t]+)[\t ]*{/s", $value, $match):
					$body = $this->extractBody ( substr ( $value, strlen ( $match [0] ) ) );
					$raw = $match [0] . $body . "}";
					return $this->advanceWithToken ( $raw, [
						"type" => $match [1],
						"raw" => $raw,
						"identifier" => $match [2],
						"value" => $body,
					]);
				default:
					return $this->advanceWithToken ( $value [0], [
						"type" => "unknown",
						"raw" => $value [0],
					]);
			}
		}

		function getAST () {
			$children = [];
			do {
				$node = $this->nextToken ();
				array_push ( $children, $node );
			} while ( $node ["type"] !== "eot" );
			return [
				"type" => "root",
				"value" => $children,
			];
		}

		function visit ( $root, $fn ) {
			$modified = array_map ( $fn, $root ["value"] );
			$root ["value"] = $modified;
			return $root;
		}

		function hasType ( $root, $type ) {
			$seen = false;
			$this->visit ( $root, function ( $node ) use ( &$seen, $type ) {
				if ( $node ["type"] === $type ) {
					$seen = true;
				}
			});
			return $seen;
		}

		function getString ( $root, $key = "raw" ) {
			$string = "";
			$this->visit ( $root, function ( $node ) use ( &$string, $key ) {
				$string .= $node [ $key ];
			});
			return $string;
		}

	}
