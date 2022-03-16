<?php

	namespace JetRails\Varnish\Console\Command\Vcl;

	use Exception;
	use JetRails\Varnish\Helper\VclGenerator;
	use Magento\PageCache\Console\Command\GenerateVclCommand;
	use Magento\PageCache\Model\Varnish\VclTemplateLocator;
	use Magento\PageCache\Model\VclTemplateLocatorInterface;
	use Magento\PageCache\Model\VclGeneratorInterfaceFactory;
	use Magento\Framework\Filesystem\DriverPool;
	use Magento\Framework\Filesystem\File\WriteFactory;
	use Magento\Framework\App\Config\ScopeConfigInterface;
	use Magento\Framework\Serialize\Serializer\Json;
	use Magento\Framework\Console\Cli;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Output\OutputInterface;

	/**
	 * @version         3.0.1
	 * @package         JetRails® Varnish
	 * @author          Rafael Grigorian - JetRails®
	 * @copyright       JetRails®, all rights reserved
	 * @license         The JetRails License (SEE LICENSE IN LICENSE.md)
	 */
	class GenerateCustom extends Command {

		protected $vclTemplateLocator;
		protected $vclGeneratorFactory;
		protected $writeFactory;
		protected $generator;

		const EXPORT_VERSION_OPTION = "export-version";
		const OUTPUT_FILE_OPTION = "output-file";

		public function __construct (
			VclTemplateLocatorInterface $vclTemplateLocator,
			VclGeneratorInterfaceFactory $vclGeneratorFactory,
			WriteFactory $writeFactory,
			VclGenerator $generator
		) {
			parent::__construct ();
			$this->vclTemplateLocator = $vclTemplateLocator;
			$this->vclGeneratorFactory = $vclGeneratorFactory;
			$this->writeFactory = $writeFactory;
			$this->generator = $generator;
		}

		protected function configure () {
			$this->setName ("varnish:vcl:generate-custom")
				->setDescription ("Generates Varnish companion VCL and echos it to the command line")
				->setDefinition ( $this->getOptionList () );
		}

		protected function execute ( InputInterface $input, OutputInterface $output ) {
			try {
				$outputFile = $input->getOption ( self::OUTPUT_FILE_OPTION );
				$varnishVersion = $input->getOption ( self::EXPORT_VERSION_OPTION );
				$vcl = $this->vclTemplateLocator->getTemplate ( $varnishVersion );
				$vcl = $this->generator->generateCustom ( $vcl );
				if ( $outputFile ) {
					$writer = $this->writeFactory->create ( $outputFile, DriverPool::FILE, "w+" );
					$writer->write ( $vcl );
					$writer->close ();
				}
				else {
					$output->writeln ( $vcl );
				}
				return Cli::RETURN_SUCCESS;
			}
			catch ( Exception $e ) {
				$output->writeln ("<error>" . $e->getMessage () . "</error>");
				if ($output->getVerbosity () >= OutputInterface::VERBOSITY_VERBOSE ) {
					$output->writeln ( $e->getTraceAsString () );
				}
				return Cli::RETURN_FAILURE;
			}
		}

		private function getOptionList () {
			return [
				new InputOption(
					self::EXPORT_VERSION_OPTION,
					null,
					InputOption::VALUE_REQUIRED,
					'The version of Varnish file',
					VclTemplateLocator::VARNISH_SUPPORTED_VERSION_4
				),
				new InputOption(
					self::OUTPUT_FILE_OPTION,
					null,
					InputOption::VALUE_REQUIRED,
					'Path to the file to write vcl'
				),
			];
		}

	}
