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
			if ( is_array( $key ) ) {
				$return = $this->data;

				foreach ( $key as $k ) {
					if ( isset( $return[ $k ] ) ) {
						$return = $return[ $k ];
						continue;
					}
					$return = $default;
					break;
				}

				return $return;
			}

			return $this->data[ $key ] ?? $default;
		}

		return $this->data;
	}

	public function setData( $value, $key = null ): void
	{
		if ( ! isset( $this->data ) ) {
			$this->getData();
		}

		if ( $key ) {
			if ( is_array( $key ) ) {
				$key = array_reverse( $key );
				$set = $value;

				foreach ( $key as $k ) {
					$set = [ $k => $set ];
				}

				$this->data = array_replace_recursive( $this->data, $set );
			} else {
				$this->data[ $key ] = $value;
			}
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
