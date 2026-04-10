<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\File\File;
use SyncEngine\Blueprint\Type\BlueprintTypeInterface;
use SyncEngine\Exception\InvalidParameterException;
use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\ModelImporter;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Service\Locator\Blueprints;
use SyncEngine\Service\Tag\Cleaner\DiscardList;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Structure\Data\ConfigData;

class BlueprintModel extends ServiceModel implements Configurable
{
	use Config {
		setConfig as private _setConfig;
		getConfig as private _getConfig;
		getConfigDependencies as private _getConfigDependencies;
	}

	const SERVICE = Blueprints::class;

	private Supervisable $supervisable;
	private File $file;
	private ?array $supervisableRawConfig = null;

	/**
	 * The version of this blueprint.
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * The author of this blueprint.
	 *
	 * @var string
	 */
	protected string $author = '';

	/**
	 * The type of blueprint, can be used for categorizing.
	 *
	 * @var string|BlueprintTypeInterface
	 */
	protected string|BlueprintTypeInterface $type = '';

	/**
	 * The entity that this blueprint is made for.
	 *
	 * @var string
	 */
	protected string $entity;

	/**
	 * Icon used in the interface.
	 *
	 * @var string
	 */
	protected string $icon = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * Human-readable description used in the interface.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Fields to configure blueprint.
	 *
	 * @var array
	 */
	protected array $fields = [];

	/**
	 * Template to setup blueprint.
	 *
	 * @var array
	 */
	protected array $template;

	public function __construct( array|string $blueprint = [] )
	{
		parent::__construct();

		if ( $blueprint ) {
			$this->version     = $blueprint['version'] ?? '';
			$this->author      = $blueprint['author'] ?? '';
			$this->type        = $blueprint['type'] ?? '';
			$this->entity      = $blueprint['entity'] ?? '';
			$this->icon        = $blueprint['icon'] ?? '';
			$this->name        = $blueprint['name'] ?? '';
			$this->description = $blueprint['description'] ?? '';
			$this->fields      = $blueprint['fields'] ?? [];
			$this->template    = $blueprint['template'] ?? [];
		}

		$this->init();

		if ( ! $this->version || ! $this->name || ! $this->entity || ! $this->template ) {
			throw new InvalidParameterException( 'Missing required Blueprint properties' );
		}
	}

	/**
	 * Initialize the blueprint.
	 * Prevents the need to overwrite the constructor.
	 */
	protected function init(): void {}

	/**
	 * @todo Better name?
	 */
	public function beforeUpdate(): void {}

	/**
	 * @todo Better name?
	 */
	public function afterUpdate(): void {}

	public function parseConfig( $config ): array
	{
		return $config;
	}

	/**
	 * Update the entity managed by this blueprint.
	 */
	final public function update( $import = false ): void
	{
		$this->beforeUpdate();

		$model = $this->getSupervisable();

		$model->setSupervisor( $this );

		if ( $import ) {
			$this->importDependencies();
		}

		// Recreate dependency array from config.
		if ( $model instanceof Configurable ) {
			if ( method_exists( $model, 'fetchConfigDependencies' ) ) {
				$model->fetchConfigDependencies();
			}
			// @todo Use interfaces?
			if ( method_exists( $model, 'updateConfig' ) ) {
				// Remove actual config and enforce blueprint class before storing in DB.
				$model->updateConfig(
					$this->clearConfig(
						array_replace_recursive( $model->getConfig(), static::initBlueprintConfig() )
					)
				);
			}
		}

		$this->afterUpdate();
	}

	final public function importDependencies(): void
	{
		$template = $this->getParsedTemplate();

		array_shift( $template );

		if ( $template ) {
			/** @var ModelImporter $importer */
			foreach ( $template as $key => $value ) {
				// Do not override existing config or data.
				// @todo Improve importer to only import if non-existend yet but do recurse into dependencies.
				unset( $value['config'] );
				unset( $value['data'] );
				$template[ $key ] = $value;
			}
			$this->getContainer()->get( ModelImporter::class )->import( $template );
		}
	}

	public function getConfigDependencies( bool|array $recursive = false ): array {
		/** @var ModelNormalizer $normalizer */
		$normalizer = $this->getContainer()->get( ModelNormalizer::class );

		$dependencies = $normalizer->getConfigDependencies(
			$this->getBlueprintConfig(),
			$this->getConfigFields(),
			$recursive
		);

		foreach ( $this->getTemplate() as $ref => $template ) {
			if ( empty( $template['_entity'] ) ) {
				continue;
			}
			$dependencies = $normalizer->getEntityDependency( $template['_entity'], $ref, $dependencies );
		}

		return $dependencies;
	}

	/**
	 * Remove config except tagged as private (_)
	 */
	final public function clearConfig( $config )
	{
		foreach ( $config as $key => $value ) {
			// Remove everything except private config.
			if ( ! str_starts_with( $key, '_' ) ) {
				unset( $config[ $key ] );
			}
		}

		$blueprintConfig = $config['_blueprint'] ?? [];

		if ( $blueprintConfig ) {
			$config[ '_blueprint' ] = ConfigData::create( $blueprintConfig )->sanitize( $this->getConfigFields() );
		}

		return $config;
	}

	final public function getBlueprintConfig(): array
	{
		return $this->getSupervisable()->getConfig( '_blueprint' ) ?? static::initBlueprintConfig()['_blueprint'] ?? [ '_class' => static::_getClassLocator() ];
	}

	final public static function initBlueprintConfig(): array
	{
		return [ '_blueprint' => [ '_class' => static::_getClassLocator() ] ];
	}

	final public function initSupervisableConfig( ?array $config = null ): void
	{
		$supervisable = $this->getSupervisable();
		$this->supervisableRawConfig = ConfigData::create( $supervisable->getEntity()->getConfig() )->normalize();
		$supervisable->setConfig(
			array_merge(
				$this->clearConfig( $this->getSupervisableRawConfig() ),
				$config ?? $this->getConfig()
			)
		);
	}

	final public function getSupervisableRawConfig(): ?array
	{
		return $this->supervisableRawConfig;
	}

	/**
	 * This returns the parsed configuration based on the entity blueprint config and blueprint template.
	 */
	final public function getConfig( $key = null, $default = null ): mixed
	{
		if ( empty( $this->config ) ) {
			$this->setConfig();
		}

		return $this->_getConfig( $key, $default );
	}

	/**
	 * The blueprint config is the actual parsed configuration based on the entity blueprint config and blueprint template.
	 */
	final public function setConfig(): void
	{
		$config = $this->getParsedTemplate( '_supervisable', 'config' );
		$config = $this->parseConfig( $config );
		$this->_setConfig( $config );
	}

	final public function getParsedTemplate( ?string $ref = null, ?string $property = null ): array
	{
		$config   = $this->getBlueprintConfig();
		$template = $this->getTemplate( $ref, $property );

		if ( empty( $template ) ) {
			// @todo Error.
			return [];
		}

		return ( new TagParser( [ 'blueprint' => $config ], recurse: false ) )
			->setCleanMode( new DiscardList( [ 'blueprint' ] ) )
			->parseArray( $template );
	}

	final public function getVersion(): string
	{
		return $this->version;
	}

	final public function getFields(): array
	{
		return $this->fields;
	}

	final public function getTemplate( ?string $ref = null, ?string $property = null ): array
	{
		$template = $this->template;

		if ( $ref ) {
			if ( '_supervisable' === $ref ) {
				// The first template item is the actual entity for this blueprint.
				$template = reset( $template );
			} else {
				$template = $template[ $ref ] ?? [];
			}

			if ( $property ) {
				$template = $template[ $property ] ?? [];
			}
		}

		return $template;
	}

	final public function getType(): string
	{
		return $this->type;
	}

	final public function getEntity(): string
	{
		// @todo Parse entity name?
		return ucfirst( $this->entity );
	}

	final public function getIcon(): string
	{
		return $this->icon ?: ( $this->isFromModule() ? $this->getModule()->getIcon() : '' );
	}

	final public function getName(): string
	{
		return $this->name;
	}

	final public function getDescription(): string
	{
		return $this->description;
	}

	final public function getAuthor(): string
	{
		return $this->author;
	}

	final public function setSupervisable( Supervisable $supervisable ): void
	{
		if ( ! $supervisable instanceof AbstractModel ) {
			throw new InvalidParameterException( 'Incorrect Model' );
		}

		if ( $supervisable::getModelName() !== $this->getEntity() ) {
			throw new InvalidParameterException( 'Incorrect Model Entity' );
		}

		$this->supervisable = $supervisable;
	}

	final public function getSupervisable(): ?Supervisable
	{
		return $this->supervisable ?? null;
	}

	final public function isFile(): bool
	{
		return ! empty( $this->file );
	}

	final public function setFile( File $file ): void
	{
		$this->file = $file;
	}

	final public function getFile(): ?File
	{
		return $this->file ?? null;
	}

	final public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'entity'      => $this->getEntity(),
			'version'     => $this->getVersion(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields(),
			'author'      => $this->getAuthor(),
			'icon'        => $this->getIcon(),
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}

	/**
	 * Returns the base config for this blueprint to be used as dependencies in other blueprint templates.
	 *
	 * @param array $config
	 *
	 * @return array{ supervisor: string, config: array{ _blueprint: array{ _class: string } } }
	 */
	public static function getDependencyTemplate( array $config = [] ): array
	{
		return [
			'supervisor' => static::getSupervisorRef(),
			'config'     => static::initBlueprintConfig(),
			...$config,
		];
	}

	final public static function getSupervisorRef(): ?string
	{
		return self::getModelName() . ':' . static::_getClassLocator();
	}

	final public function getClassLocator(): string
	{
		return ( $this->isFile() ) ? $this->getFile()->getFilename() : parent::getClassLocator();
	}
}
