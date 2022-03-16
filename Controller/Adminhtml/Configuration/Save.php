<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Configuration;

	use Magento\Framework\App\Action\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\App\Cache\TypeListInterface;
	use Magento\Framework\Controller\ResultFactory;
	use Magento\Framework\App\Cache\Type\Config;

	/**
	 * @version         3.0.1
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Save extends Action {

		protected $_configWriter;
		protected $_cacheTypeList;

		public function __construct (
			Context $context,
			WriterInterface $configWriter,
			TypeListInterface $cacheTypeList,
			array $dataArray = []
		) {
			parent::__construct ( $context, $dataArray );
			$this->_configWriter = $configWriter;
			$this->_cacheTypeList = $cacheTypeList;
		}

		protected function _isAllowed () {
			return $this->_authorization->isAllowed ("JetRails_Varnish::configuration");
		}

		public function execute () {
			if ( $debug = $this->getRequest ()->getParam ("debug") ) {
				$group = "jetrails_varnish/general_configuration";
				$this->_configWriter->save ( "$group/debug", $debug == "enable" ? 2 : 1 );
				$this->_cacheTypeList->cleanType ( Config::TYPE_IDENTIFIER );
			}
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			return $redirect->setPath ("varnish/configuration/view");
		}

	}
