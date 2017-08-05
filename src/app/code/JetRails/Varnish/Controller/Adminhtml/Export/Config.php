<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Export;

	use JetRails\Varnish\Helper\Data;
	use Magento\Backend\App\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\App\Filesystem\DirectoryList;
	use Magento\Framework\App\Response\Http\FileFactory;
	use Magento\Framework\Controller\ResultFactory;
	use Magento\Framework\Filesystem\Directory\ReadFactory;
	use Magento\Framework\Module\Dir\Reader;

	class Config extends Action {

		protected $_data;
		protected $_fileFactory;
		protected $_moduleReader;
		protected $_readFactory;

		public function __construct (
			Context $context,
			Data $data,
			FileFactory $fileFactory,
			ReadFactory $readFactory,
			Reader $moduleReader
		) {
			parent::__construct ( $context );
			$this->_data = $data;
			$this->_fileFactory = $fileFactory;
			$this->_moduleReader = $moduleReader;
			$this->_readFactory = $readFactory;
		}

		private function _returnError ( $message ) {
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			$this->messageManager->addError ( $message );
			return $redirect->setPath ("adminhtml/system_config/edit/section/jetrails_varnish");
		}

		public function execute () {
			// Get the currently saved configuration
			$backendInput = $this->_data->getBackendServer ();
			$varnishInput = $this->_data->getVarnishServersWithPorts ();
			$varnishInput = array_map ( function ( $i ) { return "\"$i->host\""; }, $varnishInput );
			$varnishInput = array_unique ( $varnishInput );
			// Check to see if the inputs are valid
			if ( $backendInput === false || count ( $varnishInput ) < 1  ) {
				// If they are not valid, then generate an error message and exit
				$blame = !$backendInput ? "Backend Server" : "Varnish Servers";
				$blame = "<font color='#E22626' ><b>$blame</b></font>";
				$message  = "To export the VCL file, ensure that the $blame field is not empty ";
				$message .= "and all changes have been saved before exporting";
				return $this->_returnError ( $message );
			}
			// Define the path to the varnish configuration template
			$templatePath  = $this->_moduleReader->getModuleDir ( "etc", "JetRails_Varnish" );
			$templatePath .= "/templates/default.vcl";
			// Check to see if the template exists
			if ( !file_exists ( $templatePath ) ) {
				// If it doesn't then return an error message
				$errorMessage = "<font color='#E22626' >etc/templates/default.vcl</font>";
				$errorMessage = "Template file for VCL not accessible at $errorMessage";
				return $this->_returnError ( $errorMessage );
			}
			// Get the contents of the template file
			$template = file_get_contents ( $templatePath );
			// Define the replacements for the template file
			$replacements = [
				"{{_BACKEND_HOST_}}" => $backendInput->host,
				"{{_BACKEND_PORT_}}" => $backendInput->port,
				"{{_PURGE_ACL_}}" => implode ( ";\n\t", $varnishInput ) . ";"
			];
			// Define the output file name, and preform replacements on the template
			$filename = "default.vcl";
			$content = strtr ( $template, $replacements );
			// Serve the file to the client
			return $this->_fileFactory->create ( $filename, $content, DirectoryList::TMP );
		}

	}