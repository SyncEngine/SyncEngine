<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Routine;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Supervisor;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Structure\Data\SchemaData;

/**
 * @extends EngineModel<Routine>
 *
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class RoutineModel extends EngineModel implements Taggable, Supervisable
{
	use Tags;
	use Supervisor;

	public function __construct( ?Routine $routine = null )
	{
		parent::__construct( $routine );
	}

	/**
	 * @return TaskModel[]
	 */
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

		$variablesSchema = $this->getVariableSchema();
		$inputSchema     = $this->getInputSchema();
		$outputSchema    = $this->getOutputSchema();

		$data['_schema'] = [
			'variables' => $variablesSchema->getSchema(),
			'input'     => $inputSchema->getSchema(),
			'output'    => $outputSchema->getSchema(),
		];

		$inputFields = $inputSchema->getFields();
		$variablesFields = $variablesSchema->getFields();

		$data['_step'] = [
			'fields' => [],
			'tags'   => $outputSchema->getTags(),
		];

		if ( count( $inputFields ) ) {
			$data['_step']['fields']['input'] = [
				'label' => [
					'icon' => 'input',
					'text' => $this->trans( 'Input data' ),
				],
				'nested' => $inputFields->bulkEdit( [ 'taggable' => true ] ),
			];
		}
		if ( count( $variablesFields ) ) {
			$data['_step']['fields']['variables'] = [
				'label' => [
					'icon' => 'variable',
					'text' => $this->trans( 'Input variables' ),
				],
				'nested' => $variablesFields->bulkEdit( [ 'taggable' => true ] ),
			];
		}

		if ( 1 < count( $data['_step']['fields'] ) ) {
			$data['_step']['fields'] = [
				'_' => [
					'tabs' => $data['_step']['fields'],
				]
			];
		}

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
		return Routine::class;
	}
}
