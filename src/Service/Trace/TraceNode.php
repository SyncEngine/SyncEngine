<?php

namespace SyncEngine\Service\Trace;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Service\ResourceData;

class TraceNode extends ResourceData
{
	public static function create( $resource = [], $type = '' ): static
	{
		if ( $resource instanceof static ) {
			return $resource;
		}

		if ( is_array( $resource ) ) {
			$ref  = $resource['_ref']; // @todo Validate item.
			$name = $resource['_label'] ?? '';
			$type = ( $type ? $type . ':' : '' ) . $resource['_class'] ?? '';
		} elseif ( is_object( $resource ) ) {
			$ref  = $resource->getRef();
			$name = $resource->getName();
			if ( $resource instanceof AbstractModel ) {
				$type = $resource::getModelName();
			} else {
				$type = $resource->getClassLocator();
			}
		} else {
			$ref  = (string) $resource;
			$name = $ref;
		}

		$context = new static();
		$context->setType( $type ?? null );
		$context->setRef( $ref );
		$context->setName( $name );

		return $context;
	}

	public static function parseRef( $resource = [] ): string
	{
		if ( is_array( $resource ) ) {
			return $resource['_ref'] ?? $resource['ref'];
		} elseif ( is_object( $resource ) && method_exists( $resource, 'getRef' ) ) {
			return $resource->getRef();
		}
		return (string) $resource;
	}

	public function getMessage(): ?string
	{
		return $this->get( 'message' );
	}

	public function setMessage( string $message ): TraceNode
	{
		return $this->set( $message, 'message' );
	}

	public function getType(): ?string
	{
		return $this->get( 'type' );
	}

	public function setType( string $type ): TraceNode
	{
		return $this->set( $type, 'type' );
	}

	public function getRef(): ?string
	{
		return $this->get( 'ref' );
	}

	public function setRef( string $ref ): TraceNode
	{
		return $this->set( $ref, 'ref' );
	}

	public function getName(): ?string
	{
		return $this->get( 'name' );
	}

	public function setName( string $name ): TraceNode
	{
		return $this->set( $name, 'name' );
	}

	public function getKey(): ?string
	{
		return $this->get( 'node' );
	}
}
