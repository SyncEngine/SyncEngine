<?php

namespace App\Component;

use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use App\Model\TaskModel;

class AutomationContext extends Context
{
	protected int $current = 0;
	protected AutomationModel $automation;
	protected array $cache = [];

	public function __construct( AutomationModel $automation ) {
		$this->automation = $automation;
	}

	public function getContextCache( $ref ): mixed
	{
		return $this->cache[ $ref ] ?? null;
	}

	public function setContextCache( $ref, $value ): void
	{
		$this->cache[ $ref ] = $value;
	}

	public function getCache(): array
	{
		return $this->cache;
	}

	public function getRoot()
	{
		return reset( $this->context );
	}

	public function getIndex(): int
	{
		return $this->current;
	}

	public function getAutomation(): AutomationModel
	{
		return $this->automation;
	}

	public function getCurrent( $type = '' ): mixed
	{
		if ( $type ) {
			return $this->context[ $this->getIndex() ][ $type ] ?? null;
		}
		return $this->context[ $this->getIndex() ] ?? null;
	}

	public function getCurrentFlow(): FlowModel
	{
		return $this->getCurrent( 'flow' );
	}

	public function getCurrentStep(): StepModel
	{
		return $this->getCurrent( 'step' );
	}

	public function getCurrentTask(): TaskModel
	{
		return $this->getCurrent( 'task' );
	}

	public function setCurrent( $value, $type = '' ) {
		$this->context[ $this->getIndex() ][ $type ] = $value;
	}

	public function startFlow( FlowModel $flow )
	{
		$this->setCurrent( $flow, 'flow' );
	}

	public function startStep( StepModel $step )
	{
		$this->setCurrent( $step, 'step' );
	}

	public function startTask( TaskModel $task )
	{
		$this->setCurrent( $task, 'task' );
	}

	public function endFlow()
	{
		$this->setCurrent( null, 'flow' );
	}

	public function endStep()
	{
		$this->setCurrent( null, 'step' );
	}

	public function endTask()
	{
		$this->setCurrent( null, 'task' );
	}

	public function next( $new = true, $auto_create_new = true )
	{
		if ( ! $new ) {
			$next = $this->getCurrent( '_next' ) ;
			if ( $next ) {
				$this->current = $next;
				return $this->getCurrent();
			}
			if ( ! $auto_create_new ) {
				// @todo error.
				return null;
			}
		}

		$previous = $this->current;
		$this->context[] = [];
		$next = array_key_last( $this->context );
		$this->setCurrent( $next, '_next' );
		$this->current = $next;
		$this->setCurrent( $previous, '_prev' );
		return $this->getCurrent();
	}

	public function previous()
	{
		$previous = $this->getCurrent( '_prev' ) ;
		$this->current = $previous;
		return $this->getCurrent();
	}

	public function descend()
	{
		// @todo Implement descending.
		return $this->next();
	}

	public function ascend()
	{
		// @todo Implement ascending.
		return $this->previous();
	}
}
