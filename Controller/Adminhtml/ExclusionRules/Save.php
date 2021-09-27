<?php

	namespace JetRails\Varnish\Controller\Adminhtml\ExclusionRules;

	use JetRails\Varnish\Helper\Data;
	use JetRails\Varnish\Helper\Validator;
	use Magento\Framework\App\Action\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\App\Cache\Type\Config;
	use Magento\Framework\App\Cache\TypeListInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\Controller\ResultFactory;
	use Magento\Framework\Message\ManagerInterface;

	/**
	 * @version         2.0.0
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Save extends Action {

		protected $_data;
		protected $_message;
		protected $_cacheTypeList;
		protected $_configWriter;
		protected $_validator;

		public function __construct (
			Data $data,
			Context $context,
			ManagerInterface $message,
			TypeListInterface $cacheTypeList,
			WriterInterface $configWriter,
			Validator $validator,
			array $dataArray = []
		) {
			parent::__construct ( $context, $dataArray );
			$this->_message = $message;
			$this->_cacheTypeList = $cacheTypeList;
			$this->_configWriter = $configWriter;
			$this->_validator = $validator;
			$this->_data = $data;
		}

		protected function _isAllowed () {
			return $this->_authorization->isAllowed ("JetRails_Varnish::exclusion_rules");
		}

		protected function _consumeErrors ( $validated ) {
			foreach ( $validated->errors as $error ) {
				$this->_message->addWarning ( $error );
			}
			return $validated->values;
		}

		public function execute () {
			if ( $this->_data->isEnabled () ) {
				$group = "jetrails_varnish/cache_exclusion_patterns";
				$routes = $this->getRequest ()->getParam ("excluded_routes");
				$paths = $this->getRequest ()->getParam ("excluded_paths");
				$wildcards = $this->getRequest ()->getParam ("excluded_wildcard_patterns");
				$regExps = $this->getRequest ()->getParam ("excluded_regexp_patterns");
				$validatedRoutes = $this->_consumeErrors ( $this->_validator->routes ( $routes ) );
				$validatedWildcards = $this->_consumeErrors ( $this->_validator->wildcards ( $wildcards ) );
				$validatedRegExps = $this->_consumeErrors ( $this->_validator->regexps ( $regExps ) );
				$this->_configWriter->save ( "$group/excluded_routes", $validatedRoutes );
				$this->_configWriter->save ( "$group/excluded_wildcard_patterns", $validatedWildcards );
				$this->_configWriter->save ( "$group/excluded_regexp_patterns", $validatedRegExps );
				$this->_cacheTypeList->cleanType ( Config::TYPE_IDENTIFIER );
				$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
				return $redirect->setPath ("varnish/exclusionrules/view");
			}
			$this->messageManager->addError (
				"Before using the <b>Exclusion Rules</b> feature, <b>Varnish Cache™</b>, must be set-up."
			);
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			return $redirect->setPath ("varnish/configuration/view");
		}

	}
