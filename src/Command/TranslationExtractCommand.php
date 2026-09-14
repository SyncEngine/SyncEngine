<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;
use SyncEngine\Framework\Translation\Extractor\JsExtractor;

#[AsCommand( name: 'syncengine:translation:extract', description: 'Extract translations from a project or module', )]
final class TranslationExtractCommand extends Command
{
	public function __construct(
		#[Autowire( service: 'translation.extractor.php_ast' )]
		private readonly ExtractorInterface         $phpExtractor,

		#[Autowire( service: 'twig.translation.extractor' )]
		private readonly ExtractorInterface         $twigExtractor,

		private readonly TranslationReaderInterface $reader,

		private readonly TranslationWriterInterface $writer,

		private readonly JsExtractor                $jsExtractor,

		#[Autowire( '%kernel.project_dir%' )]
		private readonly string                     $projectDir,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument(
			'locale',
			InputArgument::OPTIONAL,
			'The locale to extract',
			'en',
		)->addArgument(
			'root',
			InputArgument::OPTIONAL,
			'Project or module root',
		)->addOption(
			'force',
			null,
			InputOption::VALUE_NONE,
			'Overwrite existing translations instead of merging them',
		)->addOption(
			'format',
			null,
			InputOption::VALUE_REQUIRED,
			'Translation output format',
			'xlf',
		)->addOption(
			'prefix',
			null,
			InputOption::VALUE_REQUIRED,
			'Prefix for newly extracted messages',
			'',
		)->addOption(
			'no-fill',
			null,
			InputOption::VALUE_NONE,
			'Do not fill newly extracted messages with their source value',
		)->addOption(
			'clean',
			null,
			InputOption::VALUE_NONE,
			'Remove translations that are no longer present in the source',
		)->addOption(
			'sort',
			null,
			InputOption::VALUE_NONE,
			'Sort extracted messages alphabetically',
		)->addOption(
			'intl-icu',
			null,
			InputOption::VALUE_NONE,
			'Extract translations into the intl-icu domain',
		)->addOption(
			'js',
			null,
			InputOption::VALUE_NONE,
			'Also extract translations from JavaScript/JSX files',
		)->addOption(
			'js-domain',
			null,
			InputOption::VALUE_REQUIRED,
			'Default domain for JavaScript extraction (default: frontend)',
			'frontend',
		);
	}

	protected function execute(
		InputInterface $input, OutputInterface $output,
	): int {
		$locale = $input->getArgument( 'locale' );
		$root   = $this->resolveRoot( $input->getArgument( 'root' ) );
		$intlIcu = $input->getOption( 'intl-icu' );

		$domain = $this->resolveDomain( $root, $intlIcu );

		$translationPath = $root . '/translations';

		$catalogue = new MessageCatalogue( $locale );

		$prefix = $input->getOption( 'no-fill' ) ? "\0" : $input->getOption( 'prefix' );

		$src = $root . '/src';

		if ( is_dir( $src ) ) {
			$this->phpExtractor->setPrefix( $prefix );
			$this->phpExtractor->extract( $src, $catalogue );
		}

		$templates = $root . '/templates';

		if ( is_dir( $templates ) ) {
			$this->twigExtractor->setPrefix( $prefix );
			$this->twigExtractor->extract( $templates, $catalogue );
		}

		if ( $input->getOption( 'js' ) ) {
			$assets = $root . '/assets';
			if ( is_dir( $assets ) ) {
				$this->jsExtractor->setPrefix( $prefix );
				$jsDomain = $input->getOption( 'js-domain' );
				// Should handle intl-icu?
				$this->jsExtractor->setDefaultDomain( $jsDomain );
				$this->jsExtractor->extract( $assets, $catalogue );
			}
		}

		$catalogue = $this->moveToDomain( $catalogue, $domain );

		if ( ! $input->getOption( 'force' ) && is_dir( $translationPath ) ) {
			$existing = new MessageCatalogue( $locale );

			$this->reader->read(
				$translationPath,
				$existing,
			);

			if ( $input->getOption( 'clean' ) ) {
				$catalogue = $this->cleanCatalogue(
					$existing,
					$catalogue,
					$domain,
				);
			} else {
				$catalogue = ( new MergeOperation(
					$existing, $catalogue,
				) )->getResult();
			}
		}

		if ( $input->getOption( 'sort' ) ) {
			$catalogue = $this->sortCatalogue( $catalogue );
		}

		if ( ! is_dir( $translationPath ) && ! mkdir( $translationPath, 0777, true ) && ! is_dir( $translationPath ) ) {
			$output->writeln(
				sprintf(
					'<error>Unable to create translation directory "%s".</error>',
					$translationPath,
				)
			);

			return Command::FAILURE;
		}

		$this->writer->write(
			$catalogue,
			$input->getOption( 'format' ),
			[
				'path'           => $translationPath,
				'default_locale' => $locale,
			],
		);

		return Command::SUCCESS;
	}

	private function resolveRoot( ?string $root ): string
	{
		if ( null === $root ) {
			return realpath( $this->projectDir );
		}

		$root = str_starts_with( $root, '/' ) ? $root : $this->projectDir . '/' . $root;

		$resolved = realpath( $root );

		if ( false === $resolved || ! is_dir( $resolved ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Root directory "%s" does not exist.',
					$root,
				)
			);
		}

		return $resolved;
	}

	private function resolveDomain(
		string $root, bool $intlIcu,
	): string {
		if ( $root === realpath( $this->projectDir ) ) {
			return $intlIcu ? 'messages+intl-icu' : 'messages';
		}

		return $intlIcu ? basename( $root ) . '+intl-icu' : basename( $root );
	}

	private function moveToDomain(
		MessageCatalogue $catalogue, string $domain,
	): MessageCatalogue {
		$result = new MessageCatalogue( $catalogue->getLocale() );

		foreach ( $catalogue->getDomains() as $sourceDomain ) {
			foreach ( $catalogue->all( $sourceDomain ) as $id => $message ) {
				$result->set( $id, $message, $domain );

				$metadata = $catalogue->getMetadata( $id, $sourceDomain );

				if ( null !== $metadata ) {
					$result->setMetadata( $id, $metadata, $domain );
				}
			}
		}

		return $result;
	}

	private function cleanCatalogue(
		MessageCatalogue $existing, MessageCatalogue $extracted, string $domain,
	): MessageCatalogue {
		$result = new MessageCatalogue( $extracted->getLocale() );

		foreach ( $extracted->all( $domain ) as $id => $message ) {
			$result->set(
				$id,
				$existing->get( $id, $domain, $message ),
				$domain,
			);

			$metadata = $existing->getMetadata( $id, $domain );

			if ( null !== $metadata ) {
				$result->setMetadata( $id, $metadata, $domain );
			}
		}

		return $result;
	}

	private function sortCatalogue(
		MessageCatalogue $catalogue,
	): MessageCatalogue {
		$result = new MessageCatalogue( $catalogue->getLocale() );

		foreach ( $catalogue->getDomains() as $domain ) {
			$messages = $catalogue->all( $domain );

			ksort( $messages );

			foreach ( $messages as $id => $message ) {
				$result->set( $id, $message, $domain );

				$metadata = $catalogue->getMetadata( $id, $domain );

				if ( null !== $metadata ) {
					$result->setMetadata( $id, $metadata, $domain );
				}
			}
		}

		return $result;
	}
}
