<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\File\File;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\TagParser;

class BlueprintModel extends ServiceModel implements Configurable
{
	use Config {
		setConfig as private _setConfig;
	}

	private Supervisable $model;
	private File $file;

	/**
	 * The version of this blueprint.
	 *
	 * @var string
	 */
	public string $version = '';

	/**
	 * The type of blueprint, can be used for categorizing.
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * The entity that this blueprint is made for.
	 *
	 * @var string
	 */
	public string $entity = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Human-readable description used in the interface.
	 *
	 * @var string
	 */
	public string $description = '';

	/**
	 * Fields to configure blueprint.
	 *
	 * @var array
	 */
	public array $fields = [];

	/**
	 * Template to setup blueprint.
	 *
	 * @var array
	 */
	public array $template = [];

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

		if ( ! $this->version || ! $this->name || ! $this->entity ) {
			throw new \Exception( 'Incorrect Blueprint' );
		}
	}

	public function update(): void
	{
		$model = $this->getModel();

		$template = $this->getTemplate();
		if ( 1 === count( $template ) ) {
			return;
		}

		unset( $template[ $model->getRef() ] );

		$config = $model->getConfig();
		if ( $config ) {
			$template = ( new TagParser( [ 'blueprint' => $config ] ) )
				->setCleanMode( true )
				->parseTagArray( $template );
		}

		if ( $template ) {
			$this->getContainer()->get('ModelImporter')->import( $template );
		}
	}

	public function parseConfig(): void
	{
		$model    = $this->getModel();
		$config   = $model->getConfig();
		$template = $this->getTemplate( $model->getRef(), 'config' );

		if ( empty( $template ) ) {
			// @todo Error.
			$this->_setConfig( [] );

			return;
		}

		$this->setConfig(
			( new TagParser( [ 'blueprint' => $config ] ) )
				->setCleanMode( true )
				->parseTagArray( $template )
		);
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	public function getTemplate( string $ref = null, string $property = null ): array
	{
		$template = $this->template;
		if ( $ref ) {
			$template = $template[ $ref ] ?? [];

			if ( $property ) {
				$template = $template[ $property ] ?? [];
			}
		}

		return $template;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getEntity(): string
	{
		return $this->entity;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setModel( Supervisable $model ): void
	{
		$this->model = $model;
	}

	public function getModel(): ?Supervisable
	{
		return $this->model ?? null;
	}

	public function isFile(): bool
	{
		return ! empty( $this->file );
	}

	public function setFile( File $file ): void
	{
		$this->file = $file;
	}

	public function getFile(): ?File
	{
		return $this->file ?? null;
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => ( $this->isFile() ) ? $this->getFile()->getFilename() : $this->getClassName(),
			'type'        => $this->getType(),
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
