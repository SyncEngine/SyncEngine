<?php

namespace App\Model\Trait;

use App\Model\Interface\Persistable;
use App\Service\ResourceData;

trait Data
{
	protected ResourceData $data;

	public function getData( $key = null, $default = null ): mixed
	{
		if ( ! isset( $this->data ) ) {
			$this->data = new ResourceData( [] );
			if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'getData' ] ) ) {
				$this->data->set( (array) $this->getEntity()->getData() );
			}
		}

		return $this->data->get( $key, $default );
	}

	public function setData( $value, $key = null ): void
	{
		if ( ! isset( $this->data ) ) {
			$this->getData();
		}

		$this->data->set( $value, $key );

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setData' ] ) ) {
			$this->getEntity()->setData( $this->data->getArrayCopy() );
		}
	}

	public function getDataFields(): array
	{
		return [];
	}
}
