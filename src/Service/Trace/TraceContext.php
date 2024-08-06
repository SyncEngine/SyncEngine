<?php

namespace SyncEngine\Service\Trace;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Service\ResourceData;

class TraceContext extends ResourceData
{
	public function __construct( object|array $resource = [], int $flags = 0, string $iteratorClass = "ArrayIterator" )
	{
		parent::__construct(
			$resource,
			$flags,
			$iteratorClass
		);
	}

	public function parse( $context, $type = '' )
	{
		if ( is_array( $context ) ) {
			$ref  = $context['_ref']; // @todo Validate item.
			$name = $context['_label'] ?? '';
			$type = ( $type ? $type . ':' : '' ) . $context['_class'] ?? '';
		} elseif ( is_object( $context ) ) {
			$ref  = $context->getRef();
			$name = $context->getName();
			if ( $context instanceof AbstractModel ) {
				$type = $context::getModelName();
			} else {
				$type = $context->getClassLocator();
			}
		} else {
			$ref  = (string) $context;
			$name = $ref;
		}

		$this->setType( $type ?? null );
		$this->setRef( $ref );
		$this->setName( $name );
	}

	public function getMessage(): string
	{
		return $this->get( 'message' );
	}

	public function setMessage( string $message ): TraceContext
	{
		return $this->set( 'message', $message );
	}

	public function getType(): string
	{
		return $this->get( 'type' );
	}

	public function setType( string $type ): TraceContext
	{
		return $this->set( 'type', $type );
	}

	public function getRef(): string
	{
		return $this->get( 'ref' );
	}

	public function setRef( string $ref ): TraceContext
	{
		return $this->set( 'ref', $ref );
	}

	public function getName(): string
	{
		return $this->get( 'name' );
	}

	public function setName( string $name ): TraceContext
	{
		return $this->set( 'name', $name );
	}
}
