<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Service\Data\ResourceData;

trait Data
{
	protected ResourceData $data;

	public function initData(): void
	{
		$this->data = new ResourceData( [] );
		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'getData' ] ) ) {
			$this->data->set( (array) $this->getEntity()->getData() );
		}
	}

	public function getData( $key = null, $default = null ): mixed
	{
		if ( ! isset( $this->data ) ) {
			$this->initData();
		}

		return $this->data->get( $key, $default );
	}

	public function setData( $value, $key = null ): void
	{
		if ( ! isset( $this->data ) ) {
			$this->initData();
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
