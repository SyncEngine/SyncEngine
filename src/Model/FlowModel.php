<?php

namespace App\Model;

use App\Entity\Flow;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Interface\Persistable;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Model\Trait\Ref;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class FlowModel implements Exportable, Configurable, Persistable
{
	use Entity;
	use Ref;
	use Config;

	public function __construct( Flow $flow )
	{
		$this->entity = $flow;
	}

	public static function getFields(): array
	{
		return [
			'steps' => [
				'label'   => 'Add steps',
				'type'    => 'entities',
				'entity'  => 'step',
				'columns' => [
					'config' => [
						'prop'      => 'tasks',
						'type'      => 'task',
						'typeLabel' => 'Task',
					],
				],
			],
		];
	}

	public static function getEntityClass(): string
	{
		return Flow::class;
	}

	/**
	 * @return StepModel[]
	 */
	public function getSteps(): array
	{
		$steps = [];

		foreach ( $this->getConfig( 'steps' ) as $stepId ) {
			$steps[] = StepModel::get( $stepId );
		}

		return $steps;
	}

	public function setSteps( array $steps ): void
	{
		$stepIds = [];
		foreach ( $steps as $step ) {
			if ( ! $step instanceof StepModel ) {
				$step = StepModel::get( $step );
			}
			if ( $step instanceof StepModel ) {
				$stepIds[] = $step->getId();
			}
		}

		$this->setConfig( $stepIds, 'steps' );
	}
}
