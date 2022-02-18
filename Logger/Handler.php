<?php

	namespace JetRails\Varnish\Logger;

	use Magento\Framework\Logger\Handler\Base;
	use Monolog\Logger as MonoLogger;

	/**
	 * @version         3.0.0
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class Handler extends Base {

		protected $fileName = "/var/log/jetrails/purge.varnish.log";
		protected $loggerType = MonoLogger::INFO;

	}
