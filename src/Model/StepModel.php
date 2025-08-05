<?php

namespace SyncEngine\Model;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Ref;
use SyncEngine\Structure\Data\ConfigData;

/**
 * @internal
 */
final class StepModel extends AbstractModel
{
	use Ref;
	use Config;

	private ?RoutineModel $routine;
	private array $meta = [];

	public static function create( int|array|self $config ): self
	{
		if ( $config instanceof self ) {
			return $config;
		}

		$step = new self();

		if ( is_numeric( $config ) ) {
			$step->setRoutine( RoutineModel::get( $config ) );
		} else {
			$step->setRoutine( RoutineModel::get( $config['routine'] ?? $config['id'] ?? null ) );
			$step->setRef( $config['_ref'] ?? $config['ref'] ?? '' );
			$step->setConfig( $config['config'] ?? [] );
			$step->setMeta( $config['_meta'] ?? [] );
		}

		$step->createRef();

		return $step;
	}

	public function getRoutine(): ?RoutineModel
	{
		return $this->routine ?? null;
	}

	public function setRoutine( ?RoutineModel $routine ): self
	{
		$this->routine = $routine;

		return $this;
	}

	public function getMeta(): array
	{
		return $this->meta;
	}

	public function setMeta( array $meta ): StepModel
	{
		$this->meta = $meta;

		return $this;
	}

	public function getName(): string
	{
		return $this->getRoutine()?->getName() ?? $this->getRef();
	}

	public function getDescription(): string
	{
		return $this->getRoutine()?->getDescription() ?? $this->getRef();
	}

	public function getRef(): string
	{
		// @todo Generate ref?
		return $this->ref ?? '';
	}

	public function setRef( string $ref ): static
	{
		$this->ref = $ref;

		return $this;
	}

	public function getInputConfig(): ConfigData
	{
		return ConfigData::create( $this->getConfig( 'input', [] ) );
	}

	public function getVariablesConfig(): ConfigData
	{
		return ConfigData::create( $this->getConfig( 'variables', [] ) );
	}

	public function normalize(): array
	{
		return [
			'_ref'     => $this->getRef(),
			'_meta'    => $this->getMeta(),
			'routine'  => $this->getRoutine()?->getId(),
			'config'   => $this->getConfig(),
		];
	}
}
