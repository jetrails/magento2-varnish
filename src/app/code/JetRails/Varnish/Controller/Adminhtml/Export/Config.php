<?php

	namespace JetRails\Varnish\Controller\Adminhtml\Export;

	use JetRails\Varnish\Helper\Data;
	use Magento\Backend\App\Action;
	use Magento\Framework\App\Action\Context;
	use Magento\Framework\App\Filesystem\DirectoryList;
	use Magento\Framework\App\Response\Http\FileFactory;
	use Magento\Framework\Controller\ResultFactory;
	use Magento\Framework\Module\Dir\Reader;

	/**
	 * Config.php - This class is a controller action and when it is triggered, it is responsible
	 * for checking the current store configuration and generating a VCL template file.  It then
	 * serves that file to the caller.
	 * @version         1.0.0
	 * @package         JetRails® Varnish
	 * @category        Export
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 */
	class Config extends Action {

		/**
		 * These internal data members include instances of helper classes that are injected into
		 * the class using dependency injection on runtime.
		 * @var         Data                _data               Instance of the Data class
		 * @var         FileFactory         _fileFactory        Instance of the FileFactory class
		 * @var         Reader              _moduleReader       Instance of the Reader class
		 */
		protected $_data;
		protected $_fileFactory;
		protected $_moduleReader;

		/**
		 * This constructor is overloaded from the parent class in order to use dependency injection
		 * to get the dependency classes that we need for this module's command actions to execute.
		 * @param       Context             $context            The context of the caller
		 * @param       Data                $data               Instance of the Data helper class
		 * @param       FileFactory         $fileFactory        Instance of the FileFactory class
		 * @param       Reader              $moduleReader       Instance of the Reader class
		 */
		public function __construct (
			Context $context,
			Data $data,
			FileFactory $fileFactory,
			Reader $moduleReader
		) {
			// Run the parent constructor
			parent::__construct ( $context );
			// Save the injected class instances
			$this->_data = $data;
			$this->_fileFactory = $fileFactory;
			$this->_moduleReader = $moduleReader;
		}

		/**
		 * This private method takes in a message and attaches it to the caller's session.  It then
		 * redirects the caller back to the store config page.
		 * @param       String              message             The error message to attach
		 * @return      ResultFactory                           ResultFactory instance with path set
		 */
		private function _returnError ( $message ) {
			// Attach error message to session and redirect caller back to store config
			$redirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			$this->messageManager->addError ( $message );
			return $redirect->setPath ("adminhtml/system_config/edit/section/jetrails_varnish");
		}

		/**
		 * This method is overloaded because the parent class Action requires it.  This method is
		 * triggered whenever the controller is reached.  It handles all the logic of the controller
		 * action.
		 * @return      FileFactory|ResultFactory               FileFactory returned on success
		 */
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