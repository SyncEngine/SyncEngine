<?php

namespace App\Model\Trait;

trait Ref
{
	private string $ref = '';

	public function getRef(): mixed
	{
		if ( ! $this->ref ) {
			if ( isset( $this->entity ) && is_callable( [ $this->entity, 'getRef' ] ) ) {
				$this->ref = $this->entity->getRef();
			}
		}

		return $this->ref;
	}

	public function createRef( $prefix = '', $postfix = '', $recreate = false ): void
	{
		if ( ! $recreate && $this->getRef() ) {
			return;
		}

		$ref = base_convert( time(), 10, 36 ) . substr( base_convert( mt_rand(), 10, 36 ), 1 );
		$ref = $prefix . $ref . $postfix;

		if ( isset( $this->entity ) && is_callable( [ $this->entity, 'setRef' ] ) ) {
			$this->entity->setRef( $ref );
		}

		$this->ref = $ref;
	}
}
