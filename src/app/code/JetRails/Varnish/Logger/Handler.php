<?php

	namespace JetRails\Varnish\Logger;

	use Magento\Framework\Logger\Handler\Base;
	use Monolog\Logger as MonoLogger;

	/**
	 * Handler.php - This class is used by Magento to define a custom log file.  These injections
	 * for runtime reflection of this custom logging class is defined within the di.xml file.
	 * @version         1.1.5
	 * @package         JetRails® Varnish
	 * @category        Logger
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         MIT https://opensource.org/licenses/MIT
	 */
	class Handler extends Base {

		/**
		 * These internal data members define the custom log file path as well as the logging
		 * severity level as defined by Mono-logger.
		 * @var         String              fileName            Specifies path of log file to create
		 * @var         MonoLogger          loggerType          Class constant for log level
		 */
		protected $fileName = "/var/log/jetrails/purge.varnish.log";
		protected $loggerType = MonoLogger::INFO;

	}
