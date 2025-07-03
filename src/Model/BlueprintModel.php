<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\File\File;
use SyncEngine\Exception\InvalidParameterException;
use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\Tag\TagParser;

class BlueprintModel extends ServiceModel implements Configurable
{
	use Config {
		setConfig as private _setConfig;
		getConfig as private _getConfig;
	}

	const SERVICE = 'Blueprints';

	private Supervisable $supervisable;
	private File $file;

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
	 * @var string
	 */
	protected string $type = '';

	/**
	 * The entity that this blueprint is made for.
	 *
	 * @var string
	 */
	protected string $entity;

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
			$this->version     = $blueprint['version'];
			$this->author      = $blueprint['author'];
			$this->type        = $blueprint['type'];
			$this->entity      = $blueprint['entity'];
			$this->name        = $blueprint['name'];
			$this->description = $blueprint['description'] ?? '';
			$this->fields      = $blueprint['fields'] ?? [];
			$this->template    = $blueprint['template'];
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

		// Remove actual config before storing in DB.
		$model->updateConfig( $this->clearConfig( $model->getConfig() ) );

		$model->setSupervisor( $this );

		if ( $import ) {
			$this->importDependencies();
		}
	}

	final public function importDependencies(): void
	{
		$template = $this->getParsedTemplate();

		array_shift( $template );

		if ( $template ) {
			$this->getContainer()->get( 'ModelImporter' )->import( $template );
		}
	}

	/**
	 * Remote config except tagged as private (_)
	 */
	final public function clearConfig( $config )
	{
		foreach ( $config as $key => $value ) {
			// Remove everything except private config.
			if ( ! str_starts_with( $key, '_' ) ) {
				unset( $config[ $key ] );
			}
		}

		return $config;
	}

	final public function setSupervisableConfig(): void
	{
		$supervisable = $this->getSupervisable();
		$supervisable->setConfig( array_merge( $this->clearConfig( $supervisable->getConfig() ), $this->getConfig() ) );
	}

	final public function getConfig( $key = null, $default = null ): mixed
	{
		if ( empty( $this->config ) ) {
			$this->setConfig();
		}

		return $this->_getConfig( $key, $default );
	}

	final public function setConfig(): void
	{
		$config = $this->getParsedTemplate( '_supervisable', 'config' );
		$config = $this->parseConfig( $config );
		$this->_setConfig( $config );
	}

	final public function getParsedTemplate( ?string $ref = null, ?string $property = null ): array
	{
		$config   = $this->getSupervisable()->getConfig( '_blueprint' );
		$template = $this->getTemplate( $ref, $property );

		if ( empty( $template ) ) {
			// @todo Error.
			return [];
		}

		return ( new TagParser( [ 'blueprint' => $config ] ) )
			->setCleanMode( true )
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
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}

	final public function getClassLocator(): string
	{
		return ( $this->isFile() ) ? $this->getFile()->getFilename() : parent::getClassLocator();
	}
}
