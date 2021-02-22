<?php

	namespace JetRails\Varnish\Observer\Save;

	use JetRails\Varnish\Model\Adminhtml\Config\Options\EnableDisable;
	use Magento\Framework\App\Cache\Type\Config;
	use Magento\Framework\App\Cache\TypeListInterface;
	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\App\Config\Storage\WriterInterface;
	use Magento\Framework\Event\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\PageCache\Model\Config as CacheConfig;
	use Magento\Store\Model\ScopeInterface;

	/**
	 * Cache.php - This observer event is triggered whenever the caching application is changed in
	 * the system configuration page under the Advanced menu. Based on the caching application,
	 * the varnish module will be disabled or enabled.
	 * @version         1.1.11
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Cache implements ObserverInterface {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         TypeListInterface       _cacheTypeList  Instance of the TypeListInterface
		 * @var         ScopeConfigInterface    _configReader   Instance of ScopeConfigInterface
		 * @var         WriterInterface         _configWriter   Instance of WriterInterface
		 */
		protected $_cacheTypeList;
		protected $_configReader;
		protected $_configWriter;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       TypeListInterface       cacheTypeList   Instance of the TypeListInterface
		 * @param       ScopeConfigInterface    configReader    Instance of ScopeConfigInterface
		 * @param       WriterInterface         configWriter    Instance of WriterInterface
		 */
		public function __construct (
			TypeListInterface $cacheTypeList,
			ScopeConfigInterface $configReader,
			WriterInterface $configWriter
		) {
			// Save the injected class instances
			$this->_cacheTypeList = $cacheTypeList;
			$this->_configReader = $configReader;
			$this->_configWriter = $configWriter;
		}

		/**
		 * This method is required because this class implements the ObserverInterface class.  This
		 * method gets executed when the registered event is fired for this class.  The event that
		 * this method will file for can be found in the events.xml file.
		 * @param       Observer            observer            Observer with event information
		 * @return      void
		 */
		public function execute ( Observer $observer ) {
			// Set the scope to be store scope
			$storeScope = ScopeInterface::SCOPE_STORE;
			// Get the caching application that was saved
			$cacheApp = $this->_configReader->getValue (
				"system/full_page_cache/caching_application",
				$storeScope
			);
			// Based on the changed caching application, set status
			$this->_configWriter->save (
				"jetrails_varnish/general_configuration/status",
				$cacheApp == CacheConfig::VARNISH ? EnableDisable::ENABLED : EnableDisable::DISABLED
			);
			// Clean the config cache so we get the right values when querying for them
			$this->_cacheTypeList->cleanType ( Config::TYPE_IDENTIFIER );
		}

	}
