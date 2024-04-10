<?php

namespace Column;

use SyncEngine\Service\Format\AddressFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class AddressTest extends BaseTestCase
{
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

		$excpected = [
			'city'    => 'Nijmegen',
			'address' => 'Kaasstraat 12',
			'zip'     => '1024 NL',
		];

		$formatted = ( new AddressFormatter( $targetSchema ) )->convert(
			$value,
			new AddressFormatter( $sourceSchema )
		);

		// Should not be changed.
		$this->assertEquals( $excpected, $formatted );
	}
}
