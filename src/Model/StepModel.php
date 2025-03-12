<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Step;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Supervisor;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\Data\SchemaData;

/**
 * @extends EngineModel<Step>
 *
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class StepModel extends EngineModel implements Taggable, Supervisable
{
	use Tags;
	use Supervisor;

	public function __construct( ?Step $step = null )
	{
		parent::__construct( $step );
	}

	public function getTasks(): array
	{
		$tasks = [];
		foreach ( $this->getConfig( 'tasks' ) ?? [] as $task ) {
			$tasks[] = $this->getContainer()->get( 'Tasks' )->get( $task['_class'] ?? '' );
		}

		return $tasks;
	}

	public function getTask( $ref ): TaskModel|null
	{
		foreach ( $this->getConfig( 'tasks' ) ?? [] as $task ) {
			if ( $task['_ref'] === $ref ) {
				return $this->getContainer()->get( 'Tasks' )->get( $task['_class'] ?? '' );
			}
		}

		return null;
	}

	public function getPreviewStructure(): array
	{
		return $this->getData( 'preview.structure' );
	}

	public function getVariableSchema(): SchemaData
	{
		return SchemaData::fromDefinitions( $this->getConfig( 'schema.variables', [] ) );
	}

	public function getInputSchema(): SchemaData
	{
		$storage = $this->getConfig( 'schema.input.storage' );
		$manual  = $this->getConfig( 'schema.input.manual' );

		$schema = $storage ? SchemaData::fromStorage( $storage ) : new SchemaData();

		if ( $manual ) {
			$schema->merge( SchemaData::fromDefinitions( $manual ) );
		}

		return $schema;
	}

	public function getOutputSchema(): SchemaData
	{
		$storage = $this->getConfig( 'schema.output.storage' );
		$manual  = $this->getConfig( 'schema.output.manual' );

		$schema = $storage ? SchemaData::fromStorage( $storage ) : new SchemaData();

		if ( $manual ) {
			$schema->merge( SchemaData::fromDefinitions( $manual ) );
		}

		return $schema;
	}

	public function normalize( $dependencies = false, $dependents = false ): array
	{
		$data = parent::normalize( $dependencies, $dependents );

		$data['schema']  = [
			'variables' => $this->getVariableSchema()->getSchema(),
			'input'     => $this->getInputSchema()->getSchema(),
			'output'    => $this->getOutputSchema()->getSchema(),
		];
		$data['preview'] = $this->getData( 'preview' );

		return $data;
	}

	public function getFields(): array
	{
		// @todo Implement fields.
		return [
			'_' => [
				'tabs' => [
					'tasks'      => [
						'required' => true,
						'label'    => [
							'icon' => 'task',
							'text' => $this->trans( 'Tasks' ),
						],
						'type'     => 'tasks',
					],
					'conditions' => [
						'indicator' => true,
						'label'     => [
							'icon' => 'condition',
							'text' => $this->trans( 'Conditions' ),
						],
						'type'      => 'conditions',
						'taggable'  => true,
					],
					'schema'     => [
						'indicator' => true,
						'label'     => [
							'icon' => 'schema',
							'text' => $this->trans( 'Schema' ),
						],
						'nested'    => [
							'variables' => [
								'label' => [
									'icon' => 'variable',
									'text' => $this->trans( 'Input variables' ),
								],
								'type'  => 'schema',
							],
							'input'     => [
								'label'     => [
									'icon' => 'input',
									'text' => $this->trans( 'Input schema' ),
								],
								'collapsed' => true,
								'nested'    => [
									'storage' => [
										'label'   => $this->trans( 'Predefined schema storage' ),
										'type'    => 'entity',
										'entity'  => 'storage',
										'query'   => [ 'where' => [ 'type' => 'schema' ] ],
										'actions' => [ 'edit', 'create' ],
									],
									'manual'  => [
										'label'       => $this->trans( 'Manual definitions' ),
										'description' => $this->trans(
											'Configure column types for each field.'
										),
										'type'        => 'schema',
									],
								],
							],
							'output'    => [
								'label'  => [
									'icon' => 'output',
									'text' => $this->trans( 'Output schema' ),
								],
								'nested' => [
									'storage' => [
										'label'   => $this->trans( 'Predefined schema storage' ),
										'type'    => 'entity',
										'entity'  => 'storage',
										'query'   => [ 'where' => [ 'type' => 'schema' ] ],
										'actions' => [ 'edit', 'create' ],
									],
									'manual'  => [
										'label'       => $this->trans( 'Manual definitions' ),
										'description' => $this->trans(
											'Configure column types for each field.'
										),
										'type'        => 'schema',
									],
								],
							],
						],
					],
				],
			],
		];
	}

	public static function getEntityClass(): string
	{
		return Step::class;
	}
}
