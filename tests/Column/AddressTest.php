<?php

namespace Column;

use SyncEngine\Service\Format\AddressFormatter;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class AddressTest extends ColumnTestCase
{
	protected string $_column = 'Address';

	public function testAddress(): void
	{
		$value = [
			'stad'     => 'Nijmegen',
			'adres'    => 'Kaasstraat 12',
			'postcode' => '1024 NL',
		];

		$targetSchema = [
			AddressFormatter::COLUMN_ADDRESS_LINE_1 => 'adres',
			AddressFormatter::COLUMN_POSTAL_CODE    => 'postcode',
			AddressFormatter::COLUMN_LOCALITY       => 'stad',
		];

		$formatted = ( new AddressFormatter( $targetSchema ) )->format( $value );

		// Should not be changed.
		$this->assertEquals( $value, $formatted );

		/**
		 * Convert schema.
		 */
		$sourceSchema = $targetSchema;

		$targetSchema = [
			AddressFormatter::COLUMN_ADDRESS_LINE_1 => 'address',
			AddressFormatter::COLUMN_POSTAL_CODE    => 'zip',
			AddressFormatter::COLUMN_LOCALITY       => 'city',
		];

		$formatted = ( new AddressFormatter( $targetSchema ) )->convert(
			$value,
			new AddressFormatter( $sourceSchema )
		);

		// Address keys should be changed to the new format.
		$excpected = [
			'city'    => 'Nijmegen',
			'address' => 'Kaasstraat 12',
			'zip'     => '1024 NL',
		];

		$this->assertEquals( $excpected, $formatted );

		/**
		 * Convert schema but check if non-address data is kept.
		 */

		$value['foo'] = 'bar';

		$formatted = ( new AddressFormatter( $targetSchema ) )->convert(
			$value,
			new AddressFormatter( $sourceSchema )
		);

		// Non-address data should not be removed.
		$excpected = [
			'city'    => 'Nijmegen',
			'address' => 'Kaasstraat 12',
			'zip'     => '1024 NL',
			'foo'     => 'bar',
		];

		$this->assertEquals( $excpected, $formatted );

		/**
		 * Strict mode.
		 */
		$targetSchema[ AddressFormatter::STRICT ] = true;

		$formatted = ( new AddressFormatter( $targetSchema ) )->convert(
			$value,
			new AddressFormatter( $sourceSchema )
		);

		// Non-address data should be removed.
		$excpected = [
			'city'    => 'Nijmegen',
			'address' => 'Kaasstraat 12',
			'zip'     => '1024 NL',
		];

		$this->assertEquals( $excpected, $formatted );

		/**
		 * Convert schema but check if non-address data is kept.
		 * This actually provides address related keys but these keys are not set in the formatter column settings.
		 */

		$value = [
			'stad'     => 'Nijmegen',
			'adres'    => 'Kaasstraat 12',
			'postcode' => '1024 NL',
			'country'  => 'nl',
			'locality' => 'bar',
		];

		$targetSchema[ AddressFormatter::STRICT ] = false;
		$targetSchema[ AddressFormatter::COLUMN_COUNTRY_CODE ] = 'country';

		$formatted = ( new AddressFormatter( $targetSchema ) )->convert(
			$value,
			new AddressFormatter( $sourceSchema )
		);

		// Non-address data should not be removed.
		$excpected = [
			'city'     => 'Nijmegen',
			'address'  => 'Kaasstraat 12',
			'zip'      => '1024 NL',
			'locality' => 'bar',
			'country'  => 'nl',
		];

		$this->assertEquals( $excpected, $formatted );
	}
}
