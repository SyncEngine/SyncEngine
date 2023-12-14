<?php

namespace SyncEngine\Service;

use SyncEngine\Model\Abstract\EntityModel;

class ExecuteTrace extends ResourceData
{
	private array $current = [];

	public function addLog( $message ): void
	{
		$key = implode( '.trace.', $this->current );

		$trace = $this->get( $key, [] );

		if ( ! isset( $trace['trace'] ) ) {
			$trace['trace'] = [];
		}
		$trace['trace'][ 'log: ' . time() ] = $message;

		ksort( $trace );
		$this->set( $trace, $key );
	}

	public function enterTrace( $model )
	{
		if ( is_array( $model ) ) {
			$ref = $model['_ref'];
			$name = $model['_label'] ?? '';
			$type = $model['_class'] ?? '';
		} elseif ( is_object( $model ) ) {
			$ref = $model->getRef();
			$name = $model->getName();
			if ( $model instanceof EntityModel ) {
				$type = $model::getEntityClass();
			} else {
				$type = $model::getClassName();
			}
		} else {
			$ref = (string) $model;
			$name = '';
			$type = '';
		}

		// Check if it is the same loop.
		$isCurrent = $ref === end( $this->current );

		$key = implode( '.trace.', $this->current );
		$current = $this->get( $key );

		if ( $isCurrent ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->set( $current, $key );

			return;
		}

		// Make sure a trace exists.
		if ( ! isset( $current['trace'] ) ) {
			$current['trace'] = [];
			$this->set( $current, $key );
		}

		$this->current[] = $ref;

		$key = implode( '.trace.', $this->current );
		$current = $this->get( $key, [] );

		if ( ! empty( $current ) ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->set( $current, $key );

			return;
		}

		$info = '';
		if ( $name !== $ref ) {
			$info = $name;
		}
		if ( $type ) {
			$info = $info ? $info . ' (' . $type . ')' : $type;
		}
		if ( $info ) {
			$current['info'] = $info;
		}

		ksort( $current );
		$this->set( $current, $key );
	}

	public function leaveTrace( $model )
	{
		if ( is_array( $model ) ) {
			$ref = $model['_ref'];
		} elseif ( is_object( $model ) ) {
			$ref = $model->getRef();
		} else {
			$ref = (string) $model;
		}

		if ( $ref === end( $this->current ) ) {
			array_pop( $this->current );
		}
	}

	public function resetTrace()
	{
		$this->current = [];
	}
}
