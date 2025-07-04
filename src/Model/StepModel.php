<?php

namespace SyncEngine\Model;

use SyncEngine\Model\Interface\Normalizable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Ref;
use SyncEngine\Structure\Data\ConfigData;

/**
 * @internal
 */
final class StepModel implements Normalizable
{
	use Ref;
	use Config;

	private RoutineModel $routine;

	public function getRoutine(): RoutineModel
	{
		return $this->routine;
	}

	public function setRoutine( RoutineModel $routine ): StepModel
	{
		$this->routine = $routine;

		return $this;
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
		return ConfigData::create( $this->getConfig( 'input' ) );
	}

	public function getVariablesConfig(): ConfigData
	{
		return ConfigData::create( $this->getConfig( 'variables' ) );
	}

	public function normalize(): array
	{
		return [
			'_ref'    => $this->getRef(),
			'routine' => $this->getRoutine()?->getId(),
			'config'  => $this->getConfig(),
		];
	}
}
