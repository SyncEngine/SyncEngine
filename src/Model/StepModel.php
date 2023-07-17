<?php

namespace App\Model;

use App\Entity\Step;
use App\Model\Interface\Exportable;
use App\Model\Trait\Conditionals;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Model\TaskModel;
use App\Model\Trait\Ref;
use App\Service\TaskService;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class StepModel implements Exportable
{
	use Entity;
	use Config;
	use Conditionals;
	use Ref;

	public function __construct( Step $step )
	{
		$this->entity = $step;
		$this->config = $step->getConfig();
	}

	public function getTasks(): array
	{
		$tasks = [];
		foreach ( $this->getConfig( 'tasks' ) ?? [] as $task ) {
			$tasks[] = TaskService::getTask( $task['_class'] ?? '' );
		}

		return $tasks;
	}

	public function getTask( $ref ): TaskModel|null
	{
		foreach ( $this->getConfig( 'tasks' ) ?? [] as $task ) {
			if ( $task['_ref'] === $ref ) {
				return TaskService::getTask( $task['_class'] ?? '' );
			}
		}

		return null;
	}

	public static function getFields(): array
	{
		// @todo Implement fields.
		return [
			'tasks' => [
				'label' => 'Tasks',
				'type'  => 'tasks',
			],
		];
	}
}
