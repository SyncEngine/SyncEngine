<?php

namespace SyncEngine\Service;

use SyncEngine\Model\Abstract\EntityModel;

class ExecuteTrace extends ResourceData
{
	private array $current = [];

	public function addLog( $message ): void
	{
		$key = implode( '.trace.', $this->current );

		$trace = $this->get( $key );

		if ( ! isset( $trace['trace'] ) ) {
			$trace['trace'] = [];
		}
		$trace['trace'][] = $message;

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

		$key = implode( '.trace.', $this->current );
		$current = $this->get( $key );
		if ( ! isset( $current['trace'] ) ) {
			$current['trace'] = [];
			$this->set( $current, $key );
		}

		$this->current[] = $ref;

		$trace = [];

		if ( $name !== $ref || $type ) {
			$trace['info'] = $name . ( $type ? ' (' . $type . ')' : '' );
		}

		$key = implode( '.trace.', $this->current );

		$this->set( $trace, $key );
	}

	public function leaveTrace()
	{
		array_pop( $this->current );
	}

	public function resetTrace()
	{
		$this->current = [];
	}
}
