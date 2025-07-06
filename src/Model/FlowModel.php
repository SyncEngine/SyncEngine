<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Flow;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Structure\Data\SequenceData;

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

	public function getStepsConfig(): array
	{
		return $this->getConfig( 'steps', [] );
	}

	/**
	 * @return StepModel[]
	 */
	public function getSteps(): array
	{
		return array_map( [ StepModel::class, 'create' ], $this->getStepsConfig() );
	}

	/**
	 * @param  array  $steps
	 * @return void
	 */
	public function setSteps( array $steps ): void
	{
		$validated = [];

		foreach ( $steps as $stepConfig ) {
			if ( is_numeric( $stepConfig ) ) {
				$routine = RoutineModel::get( $stepConfig );
				if ( $routine instanceof RoutineModel ) {
					$validated[] = $routine->getId();
				}
				continue;
			}

			if ( ! is_array( $stepConfig ) ) {
				continue;
			}

			$step = StepModel::create( $stepConfig );
			if ( $step->getRoutine() ) {
				$validated[] = $step->normalize();
			}
		}

		$this->setConfig( $validated, 'steps' );
	}

	/**
	 * @return SequenceData<StepModel>
	 */
	public function getSequence(): SequenceData
	{
		return new SequenceData(
			$this->getStepsConfig(),
			function( $config, $ref ) {
				if ( $config instanceof StepModel ) {
					return $config;
				}

				$step = StepModel::create( $config );

				if ( is_numeric( $config ) ) {
					// Use index as ref.
					$step->setRef( $ref );
				}

				return $step;
			}
		);
	}

	public function getFields(): array
	{
		return [
			'steps' => [
				'required' => true,
				'wrap'     => false,
				'label'    => $this->trans( 'Add steps' ),
				'type'     => $this->getParameter( '__experimental' ) ? 'sequence' : 'entities',
				'entity'   => 'routine',
				'config'   => $this->getParameter( '__experimental' ) ? 'entity:_step.fields' : '',
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
