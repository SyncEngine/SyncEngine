<?php

namespace SyncEngine\Structure\Data;

use SyncEngine\Column\Interface\CollectionColumnInterface;
use SyncEngine\Column\Interface\SchemaColumnInterface;
use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\FieldTypeFactory;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Structure\Collection\AbstractCollection;
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

			if ( empty( $column['column'] ) || ! is_iterable( $column['column'] ) ) {
				$column['column'] = [];
			}

			$schema[ $column['key'] ] = $column['column'];
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

		$column = ColumnModel::create( $config['_class'] );

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

	/**
	 * @return ColumnModel[]
	 */
	public function getColumns(): array
	{
		$columns = [];
		foreach ( $this->columns as $name => $column ) {
			$columns[ $name ] = $this->getColumn( $name );
		}
		return $columns;
	}

	public function getFields(): FieldCollection
	{
		$fields = new FieldCollection();

		foreach ( $this->getColumns() as $name => $column ) {
			$fields->add( $name, $column?->getInput( $this->getColumnConfig( $name ) ) ?? ( new FieldTypeFactory() )->create( [] ) );
		}

		return $fields;
	}

	public function getTags(): array
	{
		$tags = [];

		foreach ( $this->getColumns() as $name => $column ) {
			if ( $column instanceof CollectionColumnInterface ) {
				$collectionColumn = $column->getCollectionColumn();
				if ( $collectionColumn instanceof SchemaColumnInterface ) {
					$tags[ $name ] = [ $collectionColumn->getSchemaColumns()->getTags() ];
				} else {
					$tags[ $name ] = [ '' ];
				}
				continue;
			}
			if ( $column instanceof SchemaColumnInterface ) {
				$tags[ $name ] = $column->getSchemaColumns()->getTags();
				continue;
			}
			$tags[ $name ] = '';
		}

		return $tags;
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
