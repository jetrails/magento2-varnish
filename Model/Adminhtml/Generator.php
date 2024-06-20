<?php

	namespace JetRails\Varnish\Model\Adminhtml;

	use JetRails\Varnish\Helper\VclGenerator as GeneratorHelper;
	use Magento\PageCache\Model\Varnish\VclGenerator;
	use Magento\PageCache\Model\VclTemplateLocatorInterface;

	/**
	 * @version         3.0.5
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Generator extends VclGenerator {

		private $generator;

		public function __construct (
			VclTemplateLocatorInterface $vclTemplateLocator,
			GeneratorHelper $generator,
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
			$this->generator = $generator;
		}

		public function generateVcl ( $version, $inputFile = null ) {
			$config = parent::generateVcl ( $version, $inputFile );
			return $this->generator->generateDefault ( $config );
		}

	}
