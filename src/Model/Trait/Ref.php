<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Persistable;

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

		$ref = ( new \SyncEngine\Service\Ref() )->create();
		$ref = $prefix . $ref . $postfix;

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setRef' ] ) ) {
			$this->getEntity()->setRef( $ref );
		}

		$this->ref = $ref;
	}
}
