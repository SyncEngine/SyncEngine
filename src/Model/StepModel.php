<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Step;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Conditionals;
use SyncEngine\Model\Trait\Tags;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class StepModel extends EntityModel implements Taggable
{
	use Conditionals;
	use Tags;

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
						'label'    => $this->trans( 'Tasks' ),
						'type'     => 'tasks',
					],
					'conditionals' => [
						'label'    => $this->trans( 'Conditionals' ),
						'type'     => 'conditionals',
						'taggable' => true,
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
