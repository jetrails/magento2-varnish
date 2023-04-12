<?php

	namespace JetRails\Varnish\Block\Adminhtml;

	use Magento\Framework\View\Element\Template as BaseTemplate;
	use Magento\Framework\View\Element\Template\Context;
	use Magento\Framework\App\DeploymentConfig;
	use Magento\Framework\App\ProductMetadataInterface;
	use JetRails\Varnish\Helper\Data as Helper;

	/**
	 * @version         3.0.4
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Template extends BaseTemplate {

		public $helper;
		public $config;

		public function __construct (
			Context $context,
			Helper $helper,
			DeploymentConfig $config,
			ProductMetadataInterface $metadata,
			array $data = []
		) {
			parent::__construct ( $context, $data );
			$this->helper = $helper;
			$this->config = $config;
			$this->metadata = $metadata;
		}

	}
