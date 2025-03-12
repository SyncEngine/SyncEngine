<?php

namespace SyncEngine\Service\Data;

use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Service\Collection\AbstractCollection;
use Traversable;

class SchemaData implements \ArrayAccess, \Countable, \IteratorAggregate
{
	private array $schema = [];
	private array $columns = [];

	public function __construct( array $schema = [] )
	{
		foreach ( $schema as $name => $column ) {
			$this->add( $name, $column );
		}
	}

	public static function fromStorage( StorageModel|string|int $storage ): SchemaData
	{
		return StorageModel::get( $storage )?->getDataSchema() ?? new SchemaData();
	}

	/**
	 * @param  array{ key: string, column: array }  $definitions
	 *
	 * @return SchemaData
	 */
	public static function fromDefinitions( array $definitions ): SchemaData
	{
		$schema = [];

		foreach ( $definitions as $key => $column ) {
			if ( isset( $column['_class'] ) ) {
				// Associative format.
				$schema[ $key ] = $column;
				continue;
			}

			$schema[ $column['key'] ] = $column['column'] ?? [];
			if ( ! isset( $schema[ $column['key'] ]['required'] ) ) {
				$schema[ $column['key'] ]['required'] = ! empty( $column['required'] );
			}
			if ( ! isset( $schema[ $column['key'] ]['readonly'] ) ) {
				$schema[ $column['key'] ]['readonly'] = ! empty( $column['readonly'] );
			}
		}

		return new SchemaData( $schema );
	}

	public function merge( SchemaData $schemaData ): SchemaData
	{
		foreach ( $schemaData as $name => $column ) {
			$this->add( $name, $column ?? [] );
		}

		return $this;
	}

	public function add( string $name, array|ColumnModel $column ): static
	{
		if ( $column instanceof ColumnModel ) {
			$this->columns[ $name ] = $column;
			$this->schema[ $name ]  = $column->getConfig();
		} else {
			$this->schema[ $name ]  = $column;
			$this->columns[ $name ] = null;
		}

		return $this;
	}

	public function remove( string $name ): static
	{
		unset( $this->schema[ $name ] );
		unset( $this->columns[ $name ] );

		return $this;
	}

	public function getColumnNames(): array
	{
		return array_keys( $this->schema );
	}

	public function getColumn( string $name ): ?ColumnModel
	{
		if ( isset( $this->columns[ $name ] ) && $this->columns[ $name ] instanceof ColumnModel ) {
			return $this->columns[ $name ];
		}

		$config = $this->getColumnConfig( $name );
		if ( empty( $config['_class'] ) ) {
			// Generic (empty) column.
			// @todo Create a Generic column model?
			return null;
		}

		$column = ColumnModel::get( $config['_class'] );

		if ( ! $column ) {
			throw new InvalidConfigException( 'Invalid column configuration for ' . $name );
		}

		$column?->setConfig( $config );

		$this->columns[ $name ] = $column;

		return $column;
	}

	public function getColumnConfig( string $name ): ?array
	{
		return $this->schema[ $name ] ?? null;
	}

	public function getSchema(): array
	{
		return $this->schema;
	}

	public function getColumns(): array
	{
		$columns = [];
		foreach ( $this->columns as $name => $column ) {
			$columns[ $name ] = $this->getColumn( $name );
		}
		return $columns;
	}

	public function getIterator(): Traversable
	{
		foreach ( $this->getColumnNames() as $name ) {
			yield $name => $this->getColumn( $name );
		}
	}

	public function offsetExists( mixed $offset ): bool
	{
		return isset( $this->schema[ AbstractCollection::fixFloatOffset( $offset ) ] );
	}

	public function offsetGet( mixed $offset ): ?ColumnModel
	{
		return $this->getColumn( $offset );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->add( $offset, $value );
	}

	public function offsetUnset( mixed $offset ): void
	{
		$this->remove( $offset );
	}

	public function count(): int
	{
		return count( $this->schema );
	}

	public function normalize(): array
	{
		return $this->schema;
	}
}
