<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Export;

	use JetRails\Varnish\Helper\VclGenerator;
	use Magento\Backend\App\Action;
	use Magento\Backend\App\Action\Context;
	use Magento\Framework\App\Action\HttpGetActionInterface;
	use Magento\Framework\App\Filesystem\DirectoryList;
	use Magento\Framework\App\Response\Http\FileFactory;
	use Magento\PageCache\Model\Config;

	/**
	 * @version         2.0.2
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class CustomConfig extends Action implements HttpGetActionInterface {

		protected $fileFactory;
		protected $config;
		protected $generator;

		const ADMIN_RESOURCE = "Magento_Backend::system";

		public function __construct (
			Context $context,
			FileFactory $fileFactory,
			Config $config,
			VclGenerator $generator
		) {
			parent::__construct ( $context );
			$this->config = $config;
			$this->fileFactory = $fileFactory;
			$this->generator = $generator;
		}

		public function execute () {
			$fileName = "default.custom.vcl";
			$version = $this->getRequest ()->getParam ("varnish");
			switch ( $version ) {
				case 6:
					$content = $this->config->getVclFile ( Config::VARNISH_6_CONFIGURATION_PATH );
					break;
				case 5:
					$content = $this->config->getVclFile ( Config::VARNISH_5_CONFIGURATION_PATH );
					break;
				default:
					$content = $this->config->getVclFile ( Config::VARNISH_4_CONFIGURATION_PATH );
					break;
			}
			$content = $this->generator->generateCustom ( $content );
			return $this->fileFactory->create ( $fileName, $content, DirectoryList::VAR_DIR );
		}
	}
