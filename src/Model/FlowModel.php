<?php

namespace App\Model;

use App\Entity\Flow;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Interface\Persistable;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Model\Trait\Ref;
use App\Model\Trait\Relations;
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
	use Entity {
		export as private entityExport;
		persist as private entityPersist;
	}
	use Ref;
	use Config;
	use Relations;

	public function __construct( Flow $flow )
	{
		$this->entity = $flow;
		$this->config = $flow->getConfig();
	}

	public function persist( EntityManagerInterface $entityManager, $flush = false ): void
	{
		// Parse steps.
		$this->setSteps( $this->getEntity()->getSteps() );

		$this->entityPersist( $entityManager, $flush );
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

	public function export(): array
	{
		$export = $this->entityExport();

		$export[ $this->getRef() ]['steps'] = [];

		foreach ( $this->getSteps() as $step ) {
			$ref = $step->getRef();
			$export[ $this->getRef() ]['steps'][] = $ref;
			if ( ! isset( $export[ $ref ] ) ) {
				$export = array_merge( $export, $step->export() );
			}
		}

		return $export;
	}
}
