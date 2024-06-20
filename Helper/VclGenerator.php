<?php

	namespace JetRails\Varnish\Helper;

	use Error;
	use JetRails\Varnish\Helper\LazyVclParser;
	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\ProductMetadataInterface;
	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\App\Helper\AbstractHelper;

	/**
	 * @version         3.0.5
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class VclGenerator extends AbstractHelper {

		private $data;
		private $parser;
		private $metadata;
		private $magentoEdition;
		private $magentoVersion;
		private $moduleVersion;

		function __construct (
			Data $data,
			LazyVclParser $parser,
			ProductMetadataInterface $metadata
		) {
			$this->data = $data;
			$this->parser = $parser;
			$this->metadata = $metadata;
			$this->magentoVersion = $this->metadata->getVersion ();
			$this->magentoEdition = $this->metadata->getEdition ();
			$this->moduleVersion = $this->data->getModuleVersion ();
		}

		public function insertSubHooksInclude ( $root ) {
			$modified = [];
			foreach ( $root ["value"] as $node ) {
				array_push ( $modified, $node );
				if ( $node ["type"] == "import" ) {
					$value = "\"default.custom.vcl\"";
					array_push ( $modified, [
						"type" => "whitespace",
						"raw" => "\n",
					]);
					array_push ( $modified, [
						"type" => "include",
						"raw" => "include $value;\n",
						"value" => $value,
					]);
					array_push ( $modified, [
						"type" => "whitespace",
						"raw" => "\n",
					]);
				}
			}
			$root ["value"] = $modified;
			return $root;
		}

		public function insertHeaderComment ( $root ) {
			$value = implode ( "\n", [
				" * >jetrails_",
				" *",
				" * Do not alter this file directly since it is subject to overwrite. If you are",
				" * looking to customize the config, please look into the 'default.custom.vcl'",
				" * file which this config uses to implement it's hooking system.",
				" *",
				" * Generated on Magento $this->magentoEdition $this->magentoVersion with JetRails_Varnish@$this->moduleVersion",
			]);
			$comment = [
				"type" => "multi-comment",
				"value" => $value,
				"raw" => "/**\n$value\n */",
			];
			$whitespace = [
				"type" => "whitespace",
				"raw" => "\n\n",
			];
			$root ["value"] = array_merge ( [ $comment, $whitespace ], $root ["value"] );
			return $root;
		}

		public function insertSubHooks ( $node ) {
			if ( $node ["type"] == "sub" ) {
				$prefix = "custom_" . preg_replace ( "/^vcl_/", "", $node ["identifier"] );
				$value = "    call " . $prefix . "_start;" . $node ["value"] . "    call " . $prefix . "_end;";
				$raw = "sub " . $node ["identifier"] . " {\n" . $value . "\n}";
				$node ["value"] = $value;
				$node ["raw"] = $raw;
				return $node;
			}
			return $node;
		}

		public function insertDeliver ( $node ) {
			if ( $node ["type"] == "sub" && $node ["identifier"] == "vcl_deliver" ) {
				$prefixed = implode ( "\n", [
					"    if ( resp.http.JR-Debug && resp.http.JR-Debug == \"true\" ) {",
					"        if ( obj.hits > 0 ) {",
					"            set resp.http.JR-Hit-Miss = \"HIT\";",
					"        }",
					"        else {",
					"            set resp.http.JR-Hit-Miss = \"MISS\";",
					"        }",
					"        set resp.http.JR-Hit-Count = obj.hits;",
					"    }",
					"    else {",
					"        unset resp.http.JR-Exclude-By;",
					"        unset resp.http.JR-Exclude-With;",
					"        unset resp.http.JR-Current-Path;",
					"        unset resp.http.JR-Current-Route;",
					"        unset resp.http.JR-Current-Url;",
					"    }",
				]);
				$postfixed = implode ( "\n", [
					"    unset resp.http.JR-Debug;",
					"    unset resp.http.JR-Version;",
					"    set resp.http.X-Powered-By = \"Magic\";",
					"    set resp.http.Server = \"JetRails\";",
				]);
				$value = "\n$prefixed" . $node ["value"] . "$postfixed\n";
				$raw = "sub " . $node ["identifier"] . " {\n" . $value . "\n}";
				$node ["value"] = $value;
				$node ["raw"] = $raw;
				return $node;
			}
			return $node;
		}

		public function insertBackendResponse ( $node ) {
			if ( $node ["type"] == "sub" && $node ["identifier"] == "vcl_backend_response" ) {
				$prefixed = implode ( "\n", [
					"    if ( beresp.http.JR-Exclude-By ) {",
					"        set beresp.uncacheable = true;",
					"        set beresp.ttl = 0s;",
					"        return (deliver);",
					"    }",
				]);
				$value = "\n$prefixed" . $node ["value"];
				$raw = "sub " . $node ["identifier"] . " {\n" . $value . "\n}";
				$node ["value"] = $value;
				$node ["raw"] = $raw;
				return $node;
			}
			return $node;
		}

		public function modifyBackend ( $node ) {
			if ( $node ["type"] == "backend" && $node ["identifier"] == "default" ) {
				$value = explode ( "\n", $node ["value"] );
				$value = array_map ( function ( $line ) {
					return preg_match ( "/\.(host|port)|^\s*$/", $line )
					? $line
					: "# $line";
				}, $value );
				$value = implode ( "\n", $value );
				$raw = "backend default {" . $value . "}";
				$node ["value"] = $value;
				$node ["raw"] = $raw;
				return $node;
			}
			return $node;
		}

		public function modifyRecv ( $node ) {
			if ( $node ["type"] == "sub" && $node ["identifier"] == "vcl_recv" ) {
				$value = $node ["value"];
				$value = preg_replace (
					"/if\s*\(\s*!req.http.X-Magento-Tags-Pattern\s+&&\s+!req.http.X-Pool\s*\)\s*{/m",
					"if ( !req.http.X-Magento-Tags-Pattern && !req.http.X-Pool && !req.http.JR-Purge ) {",
					$value
				);
				$value = preg_replace (
					"/return\s*\(\s*synth\s*\(\s*400,\s*\"X-Magento-Tags-Pattern or X-Pool header required\"\s*\)\s*\);/m",
					"return (synth(400, \"X-Magento-Tags-Pattern or X-Pool or JR-Purge header required\"));",
					$value
				);
				$value = preg_replace (
					"/return\s*\(\s*synth\s*\(\s*200,\s*\"Purged\"\s*\)\s*\);/m",
					implode ( "\n", [
						"if ( req.http.JR-Purge ) {",
						"            ban( req.http.JR-Purge );",
						"        }",
						"        return (synth(200, \"Purged\"));",
					]),
					$value
				);
				$raw = "backend " . $node ["identifier"] . " {" . $value . "}";
				$node ["value"] = $value;
				$node ["raw"] = $raw;
				return $node;
			}
			return $node;
		}

		public function versionEndpoint ( $node ) {
			if ( $node ["type"] == "sub" && $node ["identifier"] == "vcl_recv" ) {
				$value = implode ( "\n", [
					"    if (req.method == \"GET\" && client.ip ~ purge && req.url == \"/jetrails/varnish-config/versions\") {",
					"        return (synth(200, \"Magento " . $this->magentoVersion . " / Module " . $this->moduleVersion . "\"));",
					"    }",
				]);
				$value = "\n$value\n" . $node ["value"];
				$raw = "sub " . $node ["identifier"] . " {\n" . $value . "\n}";
				$node ["value"] = $value;
				$node ["raw"] = $raw;
				return $node;
			}
			return $node;
		}

		function generateDefault ( $vcl ) {
			$this->parser->setData ( $vcl );
			$root = $this->parser->getAST ();
			if ( $this->parser->hasType ( $root, "unknown" ) ) {
				throw new Error ("Failed to customize config, please contact JetRails.");
			}
			$root = $this->insertHeaderComment ( $root );
			$root = $this->parser->visit ( $root, [ $this, "versionEndpoint" ] );
			$root = $this->parser->visit ( $root, [ $this, "modifyRecv" ] );
			$root = $this->parser->visit ( $root, [ $this, "insertBackendResponse" ] );
			$root = $this->parser->visit ( $root, [ $this, "modifyBackend" ] );
			$root = $this->parser->visit ( $root, [ $this, "insertDeliver" ] );
			$root = $this->parser->visit ( $root, [ $this, "insertSubHooks" ] );
			$root = $this->insertSubHooksInclude ( $root );
			return $this->parser->getString ( $root );
		}

		function generateCustom ( $vcl ) {
			$this->parser->setData ( $vcl );
			$root = $this->parser->getAST ();
			if ( $this->parser->hasType ( $root, "unknown" ) ) {
				throw new Error ("Failed to customize config, please contact JetRails.");
			}
			$subs = [];
			$this->parser->visit ( $root, function ( $node ) use ( &$subs ) { if ( $node ["type"] === "sub" ) array_push ( $subs, $node ["identifier"] ); } );
			$renamed = array_map ( function ( $id ) { return "custom_" . preg_replace ( "/^vcl_/", "", $id ); }, $subs  );
			$doubled = array_map ( function ( $id ) { return [ $id . "_start", $id . "_end" ]; }, $renamed  );
			$names = array_merge ( ...$doubled );
			$final = array_map ( function ( $sub ) { return "sub $sub {}"; }, $names );
			return implode ( "\n", [
				"/**",
				" * >jetrails_",
				" * ",
				" * This file should be included within the default VCL file. It exists to help",
				" * customize the default VCL without having to modify the file itself. This way,",
				" * the default VCL can be updated without having to port over any customizations",
				" * that have been made. This is achievable by creating custom subroutines and",
				" * strategically executing them in the default VCL, effectively creating a",
				" * hooking system. The available hooks can be found below in the form of",
				" * subroutines. Their names end in either '_start' or '_end', to find when these",
				" * subroutines are executed, refer to the default VCL file.",
				" * ",
				" * To keep things tidy, it is recommended that you include a description of the",
				" * issue that the given code snippet solves as well as any helpful references",
				" * that lead you to that solution.",
				" * ",
				" * Please Note: Native Varnish subroutines such as 'vcl_synth' can also be",
				" * defined here since they are not currently being used by Magento. These",
				" * subroutines will be used in the default VCL, but it is important to make sure",
				" * that said subroutine is not defined twice.",
				" */",
				"",
				implode ( "\n", $final ),
				"\n",
			]);
		}

	}
