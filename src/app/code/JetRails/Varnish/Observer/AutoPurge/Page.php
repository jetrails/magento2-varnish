<?php

	namespace JetRails\Varnish\Observer\AutoPurge;

	use JetRails\Varnish\Observer\AutoPurge;
	use Magento\Framework\Event\Observer;

	/**
	 * Page.php - This observer is triggered when the CMS page save event is fired.  It then finds
	 * the url of the CMS page and sends a URL purge request to the configured varnish servers.
	 * @version         1.1.7
	 * @package         JetRails® Varnish
	 * @category        Save
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Page extends AutoPurge {

		/**
		 * This method is required because this class implements the ObserverInterface class.  This
		 * method gets executed when the registered event is fired for this class.  The event that
		 * this method will file for can be found in the events.xml file.
		 * @param       Observer            observer            Observer with event information
		 * @return      void
		 */
		public function execute ( Observer $observer ) {
			// Check to see if event is enabled
			if ( $this->_data->isEnabled () && $this->_data->shouldPurgeAfterCmsPageSave () ) {
				// Get id and purge all urls related to route
				$pid = $observer->getPage ()->getId ();
				if ( $pid !== null ) {
					$this->_purgeUsingRoute ("cms/page/view/page_id/$pid");
					$this->_dumpCombinedMessages ("Purged varnish cache on all configured servers:");
				}
			}
		}

	}
