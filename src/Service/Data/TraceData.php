<?php

namespace SyncEngine\Service\Data;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Service\ResourceData;

class TraceData extends ResourceData
{
	private array $traverse = [];

	public function addLog( $message, string $type = 'Log', string $name = null ): static
	{
		$key = $this->getTraverseKey();

		$traceData = $this->get( $key, [] );

		if ( ! isset( $traceData['trace'] ) ) {
			$traceData['trace'] = [];
		}

		$name = $name ?: $this->createUniqueKey( $type . ': ' . microtime(true), $traceData['trace'] );

		$traceData['trace'][ $name ] = $message;

		ksort( $traceData );
		$this->set( $traceData, $key );

		return $this;
	}

	public function addError( $message ): static
	{
		$this->addLog( $message, 'Error' );

		return $this;
	}

	public function enterTrace( $model, $type = '' ): static
	{
		if ( is_array( $model ) ) {
			$ref  = $model['_ref']; // @todo Validate item.
			$name = $model['_label'] ?? '';
			$type = ( $type ? $type . ':' : '' ) . $model['_class'] ?? '';
		} elseif ( is_object( $model ) ) {
			$ref  = $model->getRef();
			$name = $model->getName();
			if ( $model instanceof AbstractModel ) {
				$type = $model::getModelName();
			} else {
				$type = $model->getClassLocator();
			}
		} else {
			$ref  = (string) $model;
			$name = $ref;
		}

		// Check if it is the same loop.
		$isCurrent = $ref === end( $this->traverse );

		$key = $this->getTraverseKey();
		$current = $this->get( $key );

		if ( $isCurrent ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->set( $current, $key );

			return $this;
		}

		// Make sure a trace exists.
		if ( ! empty( $this->traverse ) && ! isset( $current['trace'] ) ) {
			$current['trace'] = [];
			$this->set( $current, $key );
		}

		$this->traverse[] = $ref;

		$key = $this->getTraverseKey();
		$current = $this->get( $key, [] );

		if ( ! empty( $current ) ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->set( $current, $key );

			return $this;
		}

		$current['name'] = $name;
		$current['type'] = $type;
		$current['ref']  = $ref;
		$current['time_enter'] = microtime(true);
		$current['time_leave'] = false;

		ksort( $current );
		$this->set( $current, $key );

		return $this;
	}

	public function leaveTrace( $model ): static
	{
		if ( is_array( $model ) ) {
			$ref = $model['_ref'];
		} elseif ( is_object( $model ) ) {
			$ref = $model->getRef();
		} else {
			$ref = (string) $model;
		}

		if ( $ref === end( $this->traverse ) ) {

			$key = $this->getTraverseKey();
			$current = $this->get( $key, [] );
			if ( ! empty( $current ) ) {
				$current['time_leave'] = microtime(true);
				$this->set( $current, $key );
			}

			array_pop( $this->traverse );
		}

		return $this;
	}

	public function createUniqueKey( $key, $array )
	{
		if ( ! isset( $array[ $key ] ) ) {
			return $key;
		}

		$count = 1;
		do {
			$count++;
		} while ( isset( $array[ $key . ' ' . $count ] ) );

		return $key . ' ' . $count;
	}

	public function getTraverseKey(): string
	{
		if ( $this->traverse ) {
			return 'trace.' . implode( '.trace.', $this->traverse );
		}

		return 'trace';
	}

	public function resetTraversal(): static
	{
		$this->traverse = [];//[ $this->iteration ];

		return $this;
	}
}
