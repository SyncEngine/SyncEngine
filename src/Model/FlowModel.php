<?php

namespace App\Model;

use App\Entity\Flow;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Interface\Persistable;
use App\Model\Interface\Taggable;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Model\Trait\Ref;
use App\Model\Trait\Tags;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class FlowModel implements Exportable, Configurable, Persistable, Taggable
{
	use Entity;
	use Ref;
	use Config;
	use Tags;

	public function __construct( Flow $flow )
	{
		$this->entity = $flow;
	}

	public function parseConfig(): void
	{
		// Triggers step validation.
		$this->setSteps( $this->getSteps() );
	}

	/**
	 * @return StepModel[]
	 */
	public function getSteps(): array
	{
		$steps = [];

		$stepIds = $this->getConfig( 'steps', [] );
		if ( $stepIds ) {
			foreach ( $stepIds as $stepId ) {
				//$stepId = is_numeric( $stepId ) ? $stepId : $stepId['id'];
				$steps[] = StepModel::get( $stepId );
			}
		}

		return $steps;
	}

	/**
	 * @param  array  $steps
	 * @return void
	 */
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

	public function getFields(): array
	{
		return [
			'steps' => [
				'required' => true,
				'wrap'     => false,
				'label'    => 'Add steps',
				'type'     => 'entities',
				'entity'   => 'step',
				'columns'  => [
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
}
