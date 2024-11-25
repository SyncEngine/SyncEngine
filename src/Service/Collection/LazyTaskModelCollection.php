<?php

namespace SyncEngine\Service\Collection;

use SyncEngine\Model\TaskModel;

class LazyTaskModelCollection extends TaskModelCollection
{
	/**
	 * @var TaskModel[]|array[]
	 */
	protected $collection = [];

	public function load() {
		foreach ( $this->collection as $index => $item ) {
			if ( ! $item instanceof TaskModel ) {
				$this->set( $this->create( $item ), $index );
			}
		}
	}

	public function get( string|int $offsetOrRef ): ?TaskModel
	{
		if ( is_numeric( $offsetOrRef ) ) {
			$model = $this->collection[ $offsetOrRef ] ?? null;
			if ( null === $model || $model instanceof TaskModel ) {
				return $model;
			}

			$this->set( $this->create( $model ), $offsetOrRef );
			return parent::get( $offsetOrRef );
		}

		// Full load required in order to find refs.
		$this->load();

		return $this->refs[ $offsetOrRef ] ?? null;
	}

	public function set( array|TaskModel $taskModel, ?int $offset = null ): void
	{
		if ( $taskModel instanceof TaskModel ) {
			// Already initialized.
			parent::set( $taskModel, $offset );
			return;
		}

		if ( $offset ) {
			$this->collection[ $offset ] = $taskModel;
		} else {
			$this->collection[] = $taskModel;
		}
	}

	public function remove( $offset ): void
	{
		$model = $this->collection[ $offset ] ?? null;

		if ( $model instanceof TaskModel ) {
			parent::remove( $offset );
		}

		unset( $this->collection[ $offset ] );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet( mixed $offset ): mixed
	{
		if ( is_numeric( $offset ) ) {
			return $this->collection[ $offset ] ?? null;
		}
		return $this->refs[ $offset ] ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->set( $value, $offset );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( mixed $offset ): void
	{
		$this->remove( $offset );
	}
}
