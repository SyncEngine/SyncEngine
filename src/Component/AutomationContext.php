<?php

namespace App\Component;

use App\Entity\Automation;
use App\Entity\Flow;
use App\Entity\Step;

class AutomationContext extends Context
{
	protected int $current = 0;
	protected Automation $automation;
	protected array $cache = [];

	public function __construct( Automation $automation ) {
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
