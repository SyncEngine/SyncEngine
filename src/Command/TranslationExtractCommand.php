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
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;
use SyncEngine\Framework\Translation\Extractor\JsExtractor;

#[AsCommand( name: 'syncengine:translation:extract', description: 'Extract translations from a project or module', )]
final class TranslationExtractCommand extends Command
{
	const INTL_SUFFIX = MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;

	public function __construct(
		#[Autowire( service: 'translation.extractor.php_ast' )]
		private readonly ?ExtractorInterface        $phpExtractor,
		#[Autowire( service: 'twig.translation.extractor' )]
		private readonly ?ExtractorInterface        $twigExtractor,
		private readonly JsExtractor                $jsExtractor,
		private readonly TranslationReaderInterface $reader,
		private readonly TranslationWriterInterface $writer,
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
			'Extract PHP/Twig and JS translations into the intl-icu domain',
		)->addOption(
			'js',
			null,
			InputOption::VALUE_NONE,
			'Also extract translations from JavaScript/JSX files',
		)->addOption(
			'js-domain',
			null,
			InputOption::VALUE_REQUIRED,
			'Default domain for JavaScript extraction (default: frontend; +intl-icu is appended when --intl-icu is used)',
			'frontend',
		);
	}

	protected function execute(
		InputInterface $input, OutputInterface $output,
	): int {
		$locale  = $input->getArgument( 'locale' );
		$root    = $this->resolveRoot( $input->getArgument( 'root' ) );
		$intlIcu = $input->getOption( 'intl-icu' );

		$domain = $this->resolveDomain( $root, $intlIcu );

		$translationPath = $root . '/translations';

		$coreCatalogue = new MessageCatalogue( $locale );
		$jsCatalogue   = null;

		$prefix = $input->getOption( 'no-fill' ) ? "\0" : $input->getOption( 'prefix' );

		$src = $root . '/src';

		if ( is_dir( $src ) ) {
			if ( null === $this->phpExtractor ) {
				$output->writeln(
					'<error>The PHP AST extractor is not available. Install "nikic/php-parser" to extract PHP translations.</error>'
				);

				return Command::FAILURE;
			}

			$this->phpExtractor->setPrefix( $prefix );
			$this->phpExtractor->extract( $src, $coreCatalogue );
		}

		$templates = $root . '/templates';

		if ( is_dir( $templates ) ) {
			if ( null === $this->twigExtractor ) {
				$output->writeln( '<error>The Twig translation extractor is not available.</error>' );

				return Command::FAILURE;
			}

			$this->twigExtractor->setPrefix( $prefix );
			$this->twigExtractor->extract( $templates, $coreCatalogue );
		}

		if ( $input->getOption( 'js' ) ) {
			$assets = $root . '/assets';

			if ( is_dir( $assets ) ) {
				// JS extraction always uses its own domain (--js-domain, default
				// "frontend") and never pollutes the unified PHP/Twig domain. With
				// --intl-icu the +intl-icu suffix is appended to the JS domain as
				// well, so PHP/Twig and JS translations both use ICU formatting.
				$jsCatalogue = new MessageCatalogue( $locale );

				$jsDomain = $input->getOption( 'js-domain' );

				if ( $intlIcu && ! str_ends_with( $jsDomain, self::INTL_SUFFIX ) ) {
					$jsDomain .= self::INTL_SUFFIX;
				}

				$this->jsExtractor->setPrefix( $prefix );
				$this->jsExtractor->setDefaultDomain( $jsDomain );
				$this->jsExtractor->extract( $assets, $jsCatalogue );
			}
		}

		// PHP/Twig messages always land in the unified target domain, regardless of
		// the intermediate domains the extractors used.
		$catalogue = $this->moveToDomain( $coreCatalogue, $domain );

		// JS messages keep their extracted domain(s).
		if ( null !== $jsCatalogue ) {
			$this->mergeCatalogue( $catalogue, $jsCatalogue );
		}

		if ( ! $input->getOption( 'force' ) && is_dir( $translationPath ) ) {
			$existing = new MessageCatalogue( $locale );

			$this->reader->read(
				$translationPath,
				$existing,
			);

			if ( $input->getOption( 'clean' ) ) {
				$catalogue = $this->cleanCatalogue( $existing, $catalogue );
			} else {
				$merged = ( new MergeOperation(
					$existing, $catalogue,
				) )->getResult();

				if ( ! $merged instanceof MessageCatalogue ) {
					throw new \LogicException( 'The translation merge did not return a MessageCatalogue.' );
				}

				$catalogue = $merged;
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

	/**
	 * Moves every message of the source catalogue into a single target domain.
	 */
	/**
	 * Returns every message of a catalogue keyed by its exact storage domain.
	 *
	 * MessageCatalogue::getDomains() strips the "+intl-icu" suffix and
	 * MessageCatalogue::all() merges ICU messages into the plain domain, so the
	 * plain facade API would silently move ICU messages back into the plain
	 * domain. This helper keeps both variants separate.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function getDomainMessages( MessageCatalogue $catalogue ): array
	{
		$result = [];

		foreach ( $catalogue->getDomains() as $domain ) {
			$intlDomain   = $domain . self::INTL_SUFFIX;
			$intlMessages = $catalogue->all( $intlDomain );

			$result[ $domain ] = array_diff_key( $catalogue->all( $domain ), $intlMessages );

			if ( $intlMessages ) {
				$result[ $intlDomain ] = $intlMessages;
			}
		}

		return $result;
	}

	private function moveToDomain(
		MessageCatalogue $catalogue, string $domain,
	): MessageCatalogue {
		$result = new MessageCatalogue( $catalogue->getLocale() );

		foreach ( $this->getDomainMessages( $catalogue ) as $sourceDomain => $messages ) {
			foreach ( $messages as $id => $message ) {
				$result->set( $id, $message, $domain );

				$metadata = $catalogue->getMetadata( $id, $sourceDomain );

				if ( null !== $metadata ) {
					$result->setMetadata( $id, $metadata, $domain );
				}
			}
		}

		return $result;
	}

	/**
	 * Merges all messages and metadata from the source catalogue into the target,
	 * keeping their original domains.
	 */
	private function mergeCatalogue( MessageCatalogue $target, MessageCatalogue $source ): void
	{
		foreach ( $this->getDomainMessages( $source ) as $domain => $messages ) {
			foreach ( $messages as $id => $message ) {
				$target->set( $id, $message, $domain );

				$metadata = $source->getMetadata( $id, $domain );

				if ( null !== $metadata ) {
					$target->setMetadata( $id, $metadata, $domain );
				}
			}
		}
	}

	/**
	 * Keeps only the extracted messages (in every extracted domain), preserving the
	 * existing translation values and metadata.
	 */
	private function cleanCatalogue(
		MessageCatalogue $existing, MessageCatalogue $extracted,
	): MessageCatalogue {
		$result = new MessageCatalogue( $extracted->getLocale() );

		foreach ( $this->getDomainMessages( $extracted ) as $domain => $messages ) {
			foreach ( $messages as $id => $message ) {
				$result->set(
					$id,
					$existing->has( $id, $domain ) ? $existing->get( $id, $domain ) : $message,
					$domain,
				);

				$metadata = $existing->getMetadata( $id, $domain );

				if ( null !== $metadata ) {
					$result->setMetadata( $id, $metadata, $domain );
				}
			}
		}

		return $result;
	}

	private function sortCatalogue(
		MessageCatalogue $catalogue,
	): MessageCatalogue {
		$result = new MessageCatalogue( $catalogue->getLocale() );

		foreach ( $this->getDomainMessages( $catalogue ) as $domain => $messages ) {
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
