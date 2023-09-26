<?php

namespace App\Model;

use App\Entity\Step;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Interface\Persistable;
use App\Model\Interface\Taggable;
use App\Model\Trait\Conditionals;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Model\TaskModel;
use App\Model\Trait\Ref;
use App\Model\Trait\Tags;
use App\Service\Tasks;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class StepModel implements Exportable, Configurable, Persistable, Taggable
{
	use Entity;
	use Ref;
	use Config;
	use Conditionals;
	use Tags;

	public function __construct( Step $step )
	{
		$this->entity = $step;
	}

	public function getTasks(): array
	{
		$tasks = [];
		foreach ( $this->getConfig( 'tasks' ) ?? [] as $task ) {
			$tasks[] = Tasks::getTask( $task['_class'] ?? '' );
		}

		return $tasks;
	}

	public function getTask( $ref ): TaskModel|null
	{
		foreach ( $this->getConfig( 'tasks' ) ?? [] as $task ) {
			if ( $task['_ref'] === $ref ) {
				return Tasks::getTask( $task['_class'] ?? '' );
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
						'label'    => 'Tasks',
						'type'     => 'tasks',
					],
					'conditionals' => [
						'label'    => 'Conditionals',
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
