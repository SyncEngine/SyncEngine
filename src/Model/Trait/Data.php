<?php

namespace App\Model\Trait;

use App\Model\Interface\Persistable;

trait Data
{
	protected array $data;

	public function getData( $key = null, $default = null ): mixed
	{
		if ( ! isset( $this->data ) ) {
			$this->data = [];
			if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'getData' ] ) ) {
				$this->data = (array) $this->getEntity()->getData();
			}
		}

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

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setData' ] ) ) {
			$this->getEntity()->setData( $this->data );
		}
	}

	public function getDataFields(): array
	{
		return [];
	}
}
