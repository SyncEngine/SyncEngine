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

	public function getRoot()
	{
		return reset( $this->context );
	}

	public function getDepth(): int
	{
		return $this->current;
	}

	public function getCurrent( $type = '' )
	{
		if ( $type ) {
			return $this->context[ $this->getDepth() ][ $type ] ?? null;
		}
		return $this->context[ $this->getDepth() ] ?? null;
	}

	public function getCurrentFlow(): Flow
	{
		return $this->getCurrent( 'flow' );
	}

	public function getCurrentStep(): Step
	{
		return $this->getCurrent( 'step' );
	}

	public function setCurrent( $value, $type = '' ) {
		$this->context[ $this->getDepth() ][ $type ] = $value;
	}

	public function setCurrentFlow( Flow $flow ) {
		$this->setCurrent( $flow, 'flow' );
	}

	public function setCurrentStep( Step $step ) {
		$this->setCurrent( $step, 'step' );
	}

	public function descend( $value = null )
	{
		if ( $value ) {
			$this->context[] = $value;
		}
		$this->current++;
		return $this->getCurrent();
	}

	public function ascend()
	{
		$this->current--;
		return $this->getCurrent();
	}
}
