<?php

namespace App\Model;

use App\Entity\Flow;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Model\Trait\Ref;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class FlowModel implements Exportable, Configurable
{
	use Entity;
	use Ref;
	use Config;

	const ENTITY = Flow::class;

	public function __construct( Flow $flow )
	{
		$this->entity = $flow;
		$this->config = $flow->getConfig();
	}

	public static function getFields()
	{
		// TODO: Implement getFields() method.
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

		foreach ( $this->entity->getSteps() as $stepId ) {
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

		$this->entity->setSteps( $stepIds );
	}
}
