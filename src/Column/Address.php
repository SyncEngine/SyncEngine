<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Interface\SchemaColumnInterface;
use SyncEngine\Column\Type\CollectionColumnType;
use SyncEngine\Service\Data\SchemaData;
use SyncEngine\Service\Format\AddressFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Address extends Schema implements SchemaColumnInterface
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = CollectionColumnType::TYPE;
		$this->name        = $this->trans( 'Address' );
		$this->description = $this->trans( 'Address column' );
	}

	public function getFields(): array
	{
		return [
			'strict'  => [
				'label' => $this->trans( 'Strict' ),
				'type'  => 'checkbox',
			],
			'columns' => [
				'label'  => $this->trans( 'Address columns' ),
				'collapsed' => true,
				'nested' => [
					AddressFormatter::COLUMN_COUNTRY_CODE        => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Country (Code)' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_ADMINISTRATIVE_AREA => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Administrative Area / Province / State' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_LOCALITY            => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Locality / City' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_DEPENDENT_LOCALITY  => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Dependent Locality' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_POSTAL_CODE         => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Postal / ZIP Code' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_SORTING_CODE        => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Sorting Code' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_ADDRESS_LINE_1      => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Address Line 1' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_ADDRESS_LINE_2      => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Address Line 2' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_ADDRESS_LINE_3      => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Address Line 3' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_LOCALE              => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Locale' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_ORGANIZATION        => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Organization' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_GIVEN_NAME          => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Given Name' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_ADDITIONAL_NAME     => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Additional Name' ) ] ),
						'type'  => 'text',
					],
					AddressFormatter::COLUMN_FAMILY_NAME         => [
						'label' => $this->trans( 'Column name for: {field}', [ 'field' => $this->trans( 'Family Name' ) ] ),
						'type'  => 'text',
					],
				],
			],
		];
	}

	/**
	 * @param  array|null  $config
	 *
	 * @return SchemaData
	 */
	public function getSchemaColumns( ?array $config = null ): SchemaData
	{
		return [];
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context = array_filter( $config['columns'] ?? [] );

		$context[ AddressFormatter::STRICT ] = ! empty( $config['strict'] );

		return new AddressFormatter( $context );
	}

	public function normalize(): array
	{
		$normalize = parent::normalize();

		$normalize['modalProps'] = [
			'size' => 'lg',
		];

		return $normalize;
	}
}
