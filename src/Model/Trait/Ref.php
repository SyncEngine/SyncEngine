<?php

namespace App\Model\Trait;

use App\Model\Interface\Persistable;

trait Ref
{
	private string $ref;

	public function getRef(): mixed
	{
		if ( ! isset( $this->ref ) ) {
			$this->ref = '';
			if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'getRef' ] ) ) {
				$this->ref = (string) $this->getEntity()->getRef();
			}
		}

		return $this->ref;
	}

	public function createRef( $recreate = false, $prefix = '', $postfix = '' ): void
	{
		if ( ! $recreate && $this->getRef() ) {
			return;
		}

		$ref = base_convert( (string) ( time() * 1000 ), 10, 36 );
		$ref = $ref . substr( base_convert( (string) preg_replace( '/\D/', '', rand() / getrandmax() ), 10, 36 ), 1 );
		$ref = $prefix . $ref . $postfix;

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setRef' ] ) ) {
			$this->getEntity()->setRef( $ref );
		}

		$this->ref = $ref;
	}
}
