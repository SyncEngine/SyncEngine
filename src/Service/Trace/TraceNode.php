<?php

namespace SyncEngine\Service\Trace;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Structure\Data\ResourceData;

class TraceNode extends ResourceData
{
	public static function create( $resource = [], $type = '' ): static
	{
		if ( $resource instanceof static ) {
			return $resource;
		}

		if ( is_array( $resource ) || $resource instanceof ResourceData ) {
			$ref    = $resource['_ref']; // @todo Validate item.
			$name   = $resource['_label'] ?? '';
			$type   = ( $type ? $type . ':' : '' ) . $resource['_class'] ?? '';
			$config = $resource;
		} elseif ( is_object( $resource ) ) {
			$ref    = $resource->getRef();
			$name   = $resource->getName();
			$config = is_callable( [ $resource, 'getConfig' ] ) ? $resource->getConfig() : [];
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
		if ( ! empty( $config ) ) {
			$context->setConfig( $config );
		}

		return $context;
	}

	public function parseConfigRecursive( iterable $config ): iterable
	{
		$config = ResourceData::create( $config )->normalize();

		foreach ( $config as $key => $value ) {
			if ( is_iterable( $value ) ) {
				if ( isset( $value['_ref'] ) ) {
					$config[ $key ] = $value['_ref'];
					continue;
				}
				$config[ $key ] = $this->parseConfigRecursive( $value );
			}
		}

		return $config;
	}

	public function setConfig( array $config ): TraceNode
	{
		return $this->set( $this->parseConfigRecursive( $config ), 'config' );
	}

	public function getConfig(): ?iterable
	{
		return $this->get( 'config' );
	}

	public static function parseRef( $resource = [] ): string
	{
		// @todo Remove dots(.)? Or throw exception?
		// @see https://github.com/JoryHogeveen/SyncEngine/issues/145
		if ( is_array( $resource ) || $resource instanceof ResourceData ) {
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
