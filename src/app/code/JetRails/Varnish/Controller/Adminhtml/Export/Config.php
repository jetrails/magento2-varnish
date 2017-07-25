<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Export;

	use Magento\Backend\App\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\Controller\ResultFactory;
	use Magento\Framework\Module\Dir\Reader;

	class Config extends Action {

		protected $_response;
		protected $_moduleReader;

		public function __construct ( Context $context, Reader $moduleReader ) {
			$this->_response = $context->getResponse ();
			$this->_moduleReader = $moduleReader;
			parent::__construct ( $context );
		}

		public function execute () {
			// Get the path to the template file
			$templatePath  = $this->_moduleReader->getModuleDir ( "etc", "JetRails_Varnish" );
			$templatePath .= "/templates/default.vcl";
			// Check to see if the file exists
			if ( file_exists ( $templatePath ) ) {
				// Set the headers to force download
				// Not using Magento to set headers on purpose
				header ( "JetRails-No-Cache-Blame-Module: jetrails/varnish" );
				header ( "Content-Type: application/octet-stream" );
				header ( "Content-Disposition: attachment; filename=default.vcl" );
				header ( "Content-Transfer-Encoding: binary" );
				header ( "Expires: 0" );
				header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header ( "Pragma: public" );
				header ( "Content-Length: " . filesize ( $templatePath ) );
				// Flush buffer
				ob_clean ();
				flush ();
				// Attempt to read the file, if successful, exit
				if ( ( $content = file_get_contents ( $templatePath ) ) !== false ) {
					// Output the content and exit
					echo $content;
					return true;
				}
			}
			// Report that the template file is missing
			$this->messageManager->addError ("Template file not accessible in /etc/templates");
			// Redirect back to cache management page
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			return $redirect->setPath ("adminhtml/cache/index");
		}

	}