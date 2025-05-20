<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Column\Text;
use SyncEngine\Service\Format\StringFormatter;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class TextTest extends ColumnTestCase
{
	/**
	 * @see Text
	 */
	protected string $_column = 'Text';

	public function testString(): void
	{
		$value = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce hendrerit cursus lacus sit amet dignissim.';

		$targetSchema = [
			'case' => 'ucwords',
		];

		$formatted = ( new StringFormatter( $targetSchema ) )->format( $value );

		$expected = 'Lorem Ipsum Dolor Sit Amet, Consectetur Adipiscing Elit. Fusce Hendrerit Cursus Lacus Sit Amet Dignissim.';

		$this->assertEquals( $expected, $formatted );

		/**
		 * Max characters.
		 */

		$targetSchema = [
			'max_length' => 30,
		];

		$formatted = ( new StringFormatter( $targetSchema ) )->format( $value );

		$expected = 'Lorem ipsum dolor sit amet, co';

		$this->assertEquals( $expected, $formatted );

		/**
		 * Max words.
		 */

		$targetSchema = [
			'max_length' => 10,
			'max_length_unit' => 'words',
		];

		$formatted = ( new StringFormatter( $targetSchema ) )->format( $value );

		$expected = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce hendrerit';

		$this->assertEquals( $expected, $formatted );

		/**
		 * Max words ellipsis.
		 */

		$targetSchema = [
			'max_length' => 10,
			'max_length_unit' => 'words',
			'ellipsis' => true,
		];

		$formatted = ( new StringFormatter( $targetSchema ) )->format( $value );

		$expected = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce hendrerit ...';

		$this->assertEquals( $expected, $formatted );

		/**
		 * Max words custom ellipsis.
		 */

		$targetSchema = [
			'max_length' => 10,
			'max_length_unit' => 'words',
			'ellipsis' => '[***]',
		];

		$formatted = ( new StringFormatter( $targetSchema ) )->format( $value );

		$expected = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce hendrerit [***]';

		$this->assertEquals( $expected, $formatted );
	}
}
