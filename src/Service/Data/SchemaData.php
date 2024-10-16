<?php

namespace SyncEngine\Service\Data;

use SyncEngine\Column\Interface\CollectionColumnInterface;
use SyncEngine\Column\Interface\SchemaColumnInterface;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\StorageModel;
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
		return new SchemaData( StorageModel::get( $storage )?->getDataSchema() ?? [] );
	}

	/**
	 * @param  array{ key: string, column: array }  $definitions
	 *
	 * @return SchemaData
	 */
	public static function fromDefinitions( array $definitions ): SchemaData
	{
		return new SchemaData( array_column( $definitions, 'column', 'key' ) );
	}

	public function add( string $name, array|ColumnModel $column ): static
	{
		if ( $column instanceof ColumnModel ) {
			$this->columns[ $name ] = $column;
			$this->schema[ $name ]  = $column->getConfig();
		} else {
			$this->schema[ $name ] = $column;
			$config                = $column;

			$column = ColumnModel::get( $column['_class'] );
			$column?->setConfig( $config );

			$this->columns[ $name ] = $column;
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
		return $this->columns[ $name ] ?? null;
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
		return $this->columns;
	}

	public function getIterator(): Traversable
	{
		foreach ( $this->columns as $name => $column ) {
			yield $name => $column;
		}
	}

	public function offsetExists( mixed $offset ): bool
	{
		return isset( $this->schema[ $offset ] );
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
}
