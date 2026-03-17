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
		$this->setSteps( $this->getStepsConfig() );
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
			'type' => [
				'type' => 'radio',
				'button'  => 'outline-flow',
				'subtle'  => true,
				'inline'  => true,
				'default' => 'flow',
				'choices' => [
					'flow'     => [
						'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M5.5 2.25A2.5 2.5 0 0 1 7.885 4h8.865l.219.006a4.25 4.25 0 0 1-.219 8.494h-9.5a2.75 2.75 0 1 0 0 5.5h8.865a2.5 2.5 0 1 1 0 1.5H7.25a4.25 4.25 0 0 1 0-8.5h9.5a2.75 2.75 0 0 0 .281-5.485L16.75 5.5H7.885A2.5 2.5 0 1 1 5.5 2.25"/></svg>',
						'title' => $this->trans( 'Dynamic' ),
					],
					'sequence' => [
						'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256"><path fill="currentColor" d="M208 112H48a16 16 0 0 0-16 16v24a16 16 0 0 0 16 16h160a16 16 0 0 0 16-16v-24a16 16 0 0 0-16-16m0 40H48v-24h160zm0-112H48a16 16 0 0 0-16 16v24a16 16 0 0 0 16 16h160a16 16 0 0 0 16-16V56a16 16 0 0 0-16-16m0 40H48V56h160zm-48 136a8 8 0 0 1-8 8h-16v16a8 8 0 0 1-16 0v-16h-16a8 8 0 0 1 0-16h16v-16a8 8 0 0 1 16 0v16h16a8 8 0 0 1 8 8"/></svg>',
						'title' => $this->trans( 'Sequence' ),
					]
				],
			],
			'flow' => [
				'name'     => 'steps',
				'required' => true,
				'wrap'     => false,
				'entity'   => 'routine',
				'type'     => 'flow',
				'preview'  => true,
				'conditions' => [
					'type' => [ 'operator' => '!=', 'compare' => 'sequence' ],
				],
			],
			'sequence' => [
				'name'     => 'steps',
				'required' => true,
				'wrap'     => false,
				'label'    => $this->trans( 'Add steps' ),
				'type'     => 'sequence',
				'entity'   => 'routine',
				'preview'  => true,
				'conditions' => [
					'type' => 'sequence',
				],
			],
		];
	}

	public static function getEntityClass(): string
	{
		return Flow::class;
	}
}
