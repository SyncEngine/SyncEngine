<?php

namespace App\Component;

class FlowContext extends Context
{
	protected int $current = 0;

	public function getRoot()
	{
		return reset( $this->context );
	}

	public function getCurrent()
	{
		return $this->context[ $this->getDepth() ] ?? null;
	}

	public function getDepth(): int
	{
		return $this->current;
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
