<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Flow;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Tags;

/**
 * @extends EngineModel<Flow>
 *
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class FlowModel extends EngineModel implements Taggable
{
	use Tags;

	public function __construct( ?Flow $flow = null )
	{
		parent::__construct( $flow );
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
				'label'    => $this->trans( 'Add steps' ),
				'type'     => 'entities',
				'entity'   => 'step',
				'config'   => 'entity:_flowConfig',
				'columns'  => [
					'config' => [
						'prop'      => 'tasks',
						'type'      => 'task',
						'badge' => [
							'type'  => 'task',
							'label' => [
								'type'   => 'task',
								'prefix' => $this->trans( 'Task' ) . ': ',
								'value'  => '{{ _class }}',
								'parse'  => 'tag|model',
							],
						],
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
