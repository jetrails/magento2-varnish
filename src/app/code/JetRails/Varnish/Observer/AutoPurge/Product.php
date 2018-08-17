<?php

	namespace JetRails\Varnish\Observer\AutoPurge;

	use JetRails\Varnish\Observer\AutoPurge;
	use Magento\Framework\Event\Observer;

	/**
	 * Product.php - This observer is triggered when the product save event is fired.  It then finds
	 * the url of the product and sends a URL purge request to the configured varnish servers.
	 * @version         1.1.4
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Product extends AutoPurge {

		/**
		 * This method is required because this class implements the ObserverInterface class.  This
		 * method gets executed when the registered event is fired for this class.  The event that
		 * this method will file for can be found in the events.xml file.
		 * @param       Observer            observer            Observer with event information
		 * @return      void
		 */
		public function execute ( Observer $observer ) {
			// Check to see if event is enabled
			if ( $this->_data->isEnabled () && $this->_data->shouldPurgeAfterProductSave () ) {
				// Get id and purge all urls related to route
				$pid = $observer->getProduct ()->getId ();
				if ( $pid !== null ) {
					$this->_purgeUsingRoute ("catalog/product/view/id/$pid");
					// Go through all categories associated with product and purge their urls
					foreach ( $observer->getProduct ()->getCategoryIds () as $cid ) {
						$this->_purgeUsingRoute ("catalog/category/view/id/$cid");
					}
				}
			}
		}

	}