<?php

	namespace JetRails\Varnish\Observer\AutoPurge;

	use JetRails\Varnish\Observer\AutoPurge;
	use Magento\Framework\Event\Observer;

	/**
	 * Category.php - This observer is triggered when the category save event is fired.  It then
	 * finds the url of the category and sends a URL purge request to the configured varnish
	 * servers.
	 * @version         1.1.9
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Category extends AutoPurge {

		/**
		 * This method is required because this class implements the ObserverInterface class.  This
		 * method gets executed when the registered event is fired for this class.  The event that
		 * this method will file for can be found in the events.xml file.
		 * @param       Observer            observer            Observer with event information
		 * @return      void
		 */
		public function execute ( Observer $observer ) {
			// Check to see if option is enabled
			if ( $this->_data->isEnabled () && $this->_data->shouldPurgeAfterCategorySave () ) {
				// Get id and purge all urls related to route
				$cid = $observer->getCategory ()->getId ();
				if ( $cid !== null ) {
					$this->_purgeUsingRoute ("catalog/category/view/id/$cid");
					$this->_dumpCombinedMessages ("Purged varnish cache on all configured servers:");
				}
			}
		}

	}
