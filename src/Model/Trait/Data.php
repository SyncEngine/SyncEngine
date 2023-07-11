<?php

namespace App\Model\Trait;

trait Data
{
	protected array $data = [];

	public function getData( $key = null, $default = null ): mixed
	{
		if ( $key ) {
			return $this->data[ $key ] ?? $default;
		}

		return $this->data;
	}

	public function setData( $value, $key = null ): void
	{
		if ( $key ) {
			$this->data[ $key ] = $value;
		} else {
			$this->data = $value;
		}

		if ( isset( $this->entity ) && is_callable( [ $this->entity, 'setData' ] ) ) {
			$this->entity->setData( $this->data );
		}
	}

	public function getDataFields(): array
	{
		return [];
	}
}
