<?php

	namespace JetRails\Varnish\Block\Adminhtml;

	use Magento\Framework\View\Element\Template as BaseTemplate;
	use Magento\Framework\View\Element\Template\Context;
	use Magento\Framework\App\DeploymentConfig;
	use JetRails\Varnish\Helper\Data as Helper;

	/**
	 * @version         2.0.2
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
			array $data = []
		) {
			parent::__construct ( $context, $data );
			$this->helper = $helper;
			$this->config = $config;
		}

	}
