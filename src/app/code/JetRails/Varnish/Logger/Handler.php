<?php

	namespace JetRails\Varnish\Logger;

	use Magento\Framework\Logger\Handler\Base;
	use Monolog\Logger as MonoLogger;

	class Handler extends Base {

	    protected $fileName = "/var/log/jetrails/purge.varnish.log";
	    protected $loggerType = MonoLogger::INFO;

	}