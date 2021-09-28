<?php

	namespace JetRails\Varnish\Model\Adminhtml;

	use JetRails\Varnish\Helper\Data;
	use Magento\Framework\App\ProductMetadataInterface;
	use Magento\PageCache\Model\Varnish\VclGenerator;
	use Magento\PageCache\Model\VclTemplateLocatorInterface;

	/**
	 * @version         2.0.2
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Generator extends VclGenerator {

		private $data;
		private $metadata;
		private $magentoEdition;
		private $magentoVersion;
		private $moduleVersion;
		private $header = [
			"#", "# >jetrails_", "#",
		];

		public function __construct (
			VclTemplateLocatorInterface $vclTemplateLocator,
			ProductMetadataInterface $metadata,
			Data $data,
			$backendHost,
			$backendPort,
			$accessList,
			$gracePeriod,
			$sslOffloadedHeader,
			$designExceptions = []
		) {
			parent::__construct (
				$vclTemplateLocator,
				$backendHost,
				$backendPort,
				$accessList,
				$gracePeriod,
				$sslOffloadedHeader,
				$designExceptions
			);
			$this->data = $data;
			$this->metadata = $metadata;
			$this->magentoVersion = $this->metadata->getVersion ();
			$this->magentoEdition = $this->metadata->getEdition ();
			$this->moduleVersion = $this->data->getModuleVersion ();
			$this->header = array_merge ( $this->header, [
				sprintf ( "# Config file was generated on Magento_%s@%s and JetRails_Varnish@%s.", $this->magentoEdition, $this->magentoVersion, $this->moduleVersion ),
				"# Do not alter this VCL directly since it is subject to overwrite.",
				"#"
			]);
		}

		public function generateVcl ( $version ) {
			$config = parent::generateVcl ( $version );
			$replacements = [
				"/    \.first_byte_timeout[^}]+}/s" => [
					"#   .first_byte_timeout = 600s;",
					"#   .probe = {",
					"#       .url = \"/pub/health_check.php\";",
					"#       .timeout = 2s;",
					"#       .interval = 5s;",
					"#       .window = 10;",
					"#       .threshold = 5;",
					"#  }",
				],
				"/^sub\s+vcl_recv\s*{/m" => [
					"sub vcl_recv {",
					"    if (req.method == \"GET\" && client.ip ~ purge && req.url == \"/jetrails/varnish-config/versions\") {",
					"        return (synth(200, \"Magento " . $this->magentoVersion . " / Module " . $this->moduleVersion . "\"));",
					"    }",
					"    set req.http.X-Forwarded-For = req.http.CF-Connecting-IP;",
				],
				"/if\s*\(\s*!req.http.X-Magento-Tags-Pattern\s+&&\s+!req.http.X-Pool\s*\)\s*{/m" => [
					"if ( !req.http.X-Magento-Tags-Pattern && !req.http.X-Pool && !req.http.JR-Purge ) {",
				],
				"/return\s*\(\s*synth\s*\(\s*400,\s*\"X-Magento-Tags-Pattern or X-Pool header required\"\s*\)\s*\);/m" => [
					"return (synth(400, \"X-Magento-Tags-Pattern or X-Pool or JR-Purge header required\"));",
				],
				"/return\s*\(\s*synth\s*\(\s*200,\s*\"Purged\"\s*\)\s*\);/m" => [
					"if ( req.http.JR-Purge ) {",
					"            ban( req.http.JR-Purge );",
					"        }",
					"        return (synth(200, \"Purged\"));",
				],
				"/^sub\s+vcl_backend_response\s*{/m" => [
					"sub vcl_backend_response {",
					"    if ( beresp.http.JR-Exclude-By ) {",
					"        set beresp.uncacheable = true;",
					"        set beresp.ttl = 0s;",
					"        return (deliver);",
					"    }",
				],
				"/^sub\s+vcl_deliver\s*{/m" => [
					"sub vcl_deliver {",
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
				],
				"/\s+unset\s+resp.http.Link;/m" => [
					"",
					"    unset resp.http.Link;",
					"    unset resp.http.JR-Debug;",
					"    unset resp.http.JR-Version;",
					"    set resp.http.X-Powered-By = \"Magic\";",
					"    set resp.http.Server = \"JetRails\";",
				],
			];
			foreach ( $replacements as $search => $replacement ) {
				$config = preg_replace ( $search, implode ( "\n", $replacement ), $config, 1, $count );
				if ( !$count ) {
					return "Error: Failed to customize config, please contact module vendor.";
				}
			}
			return implode ( "\n", $this->header ) . "\n\n" . $config;
		}

	}
