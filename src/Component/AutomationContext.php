<?php

namespace App\Component;

use App\Entity\Automation;
use App\Entity\Flow;
use App\Entity\Step;

class AutomationContext extends Context
{
	protected int $current = 0;
	protected Automation $automation;

	public function __construct( Automation $automation ) {
		$this->automation = $automation;
	}

	public function getContextValue( $ref )
	{
		$parts = explode( '.', $ref );
		$level = $this->context[ $parts[0] ];

		return $level[ $parts[1] ] ?? null;
	}

	public function setContextValue( $ref, $value )
	{
		$this->context[ $this->getIndex() ][ $ref ] = $value;
	}

	public function getRoot()
	{
		return reset( $this->context );
	}

	public function getIndex(): int
	{
		return $this->current;
	}

	public function getAutomation(): Automation
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

	public function getCurrentFlow(): Flow
	{
		return $this->getCurrent( 'flow' );
	}

	public function getCurrentStep(): Step
	{
		return $this->getCurrent( 'step' );
	}

	public function getCurrentTask(): object
	{
		return $this->getCurrent( 'task' );
	}

	public function setCurrent( $value, $type = '' ) {
		$this->context[ $this->getIndex() ][ $type ] = $value;
	}

	public function startFlow( Flow $flow )
	{
		$this->setCurrent( $flow, 'flow' );
	}

	public function startStep( Step $step )
	{
		$this->setCurrent( $step, 'step' );
	}

	public function startTask( object $task )
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

	public function next()
	{
		$previous = $this->current;
		$this->context[] = [];
		$this->current = array_key_last( $this->context );
		$this->setCurrent( '_prev', $previous );
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
