<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\File\File;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\TagParser;

class BlueprintModel extends ServiceModel implements Configurable
{
	use Config {
		setConfig as private _setConfig;
		getConfig as private _getConfig;
	}

	private Supervisable $supervisable;
	private File $file;

	/**
	 * The version of this blueprint.
	 *
	 * @var string
	 */
	protected string $version = '';

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
	protected string $entity = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	protected string $name = '';

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
	protected array $template = [];

	public function __construct( array|string $blueprint = [] )
	{
		parent::__construct();

		if ( $blueprint ) {
			$this->version     = $blueprint['version'];
			$this->type        = $blueprint['type'];
			$this->entity      = $blueprint['entity'];
			$this->name        = $blueprint['name'];
			$this->description = $blueprint['description'] ?? '';
			$this->fields      = $blueprint['fields'] ?? [];
			$this->template    = $blueprint['template'];
		}

		$this->init();
	}

	protected function init()
	{
		if ( ! $this->version || ! $this->name || ! $this->entity ) {
			throw new \Exception( 'Incorrect Blueprint' );
		}
	}

	final public function update(): void
	{
		$model = $this->getSupervisable();

		// Remove actual config before storing in DB.
		$model->setConfig( [
			'_blueprint' => $model->_getConfig( '_blueprint' ),
			'_dependencies' => $model->_getConfig( '_dependencies' ),
		] );

		$template = $this->getParsedTemplate();

		unset( $template[ $model->getRef() ] );

		if ( $template ) {
			$this->getContainer()->get('ModelImporter')->import( $template );
		}
	}

	final public function updateConfig(): void
	{
		$supervisable = $this->getSupervisable();
		$supervisable->setConfig( array_merge( $supervisable->_getConfig(), $this->getConfig() ) );
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
		$this->_setConfig( $this->getParsedTemplate( '_supervisable', 'config' ) );
	}

	final public function getParsedTemplate( string $ref = null, string $property = null ): array
	{
		$config   = $this->getSupervisable()->getConfig( '_blueprint' );
		$template = $this->getTemplate( $ref, $property );

		if ( empty( $template ) ) {
			// @todo Error.
			$this->_setConfig( [] );

			return [];
		}

		return ( new TagParser( [ 'blueprint' => $config ] ) )
			->setCleanMode( true )
			->parseTagArray( $template );
	}

	final public function getVersion(): string
	{
		return $this->version;
	}

	final public function getFields(): array
	{
		return $this->fields;
	}

	final public function getTemplate( string $ref = null, string $property = null ): array
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

	final public function setSupervisable( Supervisable $supervisable ): void
	{
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
			'_class'      => ( $this->isFile() ) ? $this->getFile()->getFilename() : $this->getClassName(),
			'type'        => $this->getType(),
			'entity'      => $this->getEntity(),
			'version'     => $this->getVersion(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields(),
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}
}
