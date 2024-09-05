<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Step;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Conditions;
use SyncEngine\Model\Trait\Supervisor;
use SyncEngine\Model\Trait\Tags;

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
	use Conditions;
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
			$tasks[] = $this->getContainer()->get( 'Tasks' )->getTask( $task['_class'] ?? '' );
		}

		return $tasks;
	}

	public function getTask( $ref ): TaskModel|null
	{
		foreach ( $this->getConfig( 'tasks' ) ?? [] as $task ) {
			if ( $task['_ref'] === $ref ) {
				return $this->getContainer()->get( 'Tasks' )->getTask( $task['_class'] ?? '' );
			}
		}

		return null;
	}

	public function getFields(): array
	{
		// @todo Implement fields.
		return [
			'_' => [
				'tabs' => [
					'tasks'        => [
						'required' => true,
						'label'    => [
							'icon' => 'task',
							'text' => $this->trans( 'Tasks' ),
						],
						'type'     => 'tasks',
					],
					'conditions' => [
						'indicator' => true,
						'label'    => [
							'icon' => 'condition',
							'text' => $this->trans( 'Conditions' ),
						],
						'type'      => 'conditions',
						'taggable'  => true,
					],
					'schema'     => [
						'indicator' => true,
						'label'    => [
							'icon' => 'schema',
							'text' => $this->trans( 'Schema' ),
						],
						'nested' => [
							'variables' => [
								'label' => [
									'icon' => 'braces-asterisk',
									'text' => $this->trans( 'Input variables' ),
								],
								'type'  => 'schema',
							],
							'input' => [
								'label'     => [
									'icon' => 'box-arrow-in-right',
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
									'manual' => [
										'label'       => $this->trans( 'Manual definitions' ),
										'description' => $this->trans(
											'Configure column types for each field.'
										),
										'type'        => 'schema',
									],
								],
							],
							'output' => [
								'label'  => [
									'icon' => 'box-arrow-right',
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
									'manual' => [
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
