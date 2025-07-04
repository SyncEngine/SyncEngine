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

	private ?RoutineModel $routine;

	public static function create( int|array|self $config ): self
	{
		if ( $config instanceof self ) {
			return $config;
		}

		$step = new self();

		if ( is_numeric( $config ) ) {
			$step->setRoutine( RoutineModel::get( $config ) );
		} else {
			$step->setRoutine( RoutineModel::get( $config['routine'] ?? $config['id'] ) );
			$step->setRef( $config['_ref'] ?? $config['ref'] ?? '' );
			$step->setConfig( $config['config'] ?? [] );
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
			'_ref'    => $this->getRef(),
			'routine' => $this->getRoutine()?->getId(),
			'config'  => $this->getConfig(),
		];
	}
}
