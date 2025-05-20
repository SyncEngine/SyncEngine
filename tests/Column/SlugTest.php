<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Column\Slug;
use SyncEngine\Service\Format\SlugFormatter;
use SyncEngine\Tests\TestCase\ColumnTestCase;

class SlugTest extends ColumnTestCase
{
	/**
	 * @see Slug
	 */
	protected string $_column = 'Slug';

	public function testFormatSchema(): void
	{
		// Basic
		$value = "Wôrķšƥáçè ~~sèťtïñğš~~ ";

		$targetSchema = [];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'Workspace-settings', $formatted );

		/**
		 * Simple tests.
		 */
		$value = "One two^^ three! four>.";

		// Snake Case
		$targetSchema = [
			'case'      => 'lower',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'one_two_three_four', $formatted );

		// Screaming Snake Case
		$targetSchema = [
			'case'      => 'upper',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'ONE_TWO_THREE_FOUR', $formatted );

		// Capitalized Snake Case
		$targetSchema = [
			'case'      => 'ucwords',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'One_Two_Three_Four', $formatted );

		// Camel Case
		$targetSchema = [
			'case'      => 'camel',
			'separator' => '',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'oneTwoThreeFour', $formatted );

		// Capitalized Camel Case
		$targetSchema = [
			'case'      => 'ucwords',
			'separator' => '',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'OneTwoThreeFour', $formatted );

		// Namespace Case
		$targetSchema = [
			'case'      => 'ucwords',
			'separator' => '/',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'One/Two/Three/Four', $formatted );
	}

	public function testConvertSchema(): void
	{

		// Snake to Camel > No formatter source required.
		$value = 'one_two_three_four';

		$targetSchema = [
			'case'      => 'camel',
			'separator' => '',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->convert( $value );

		$this->assertEquals( 'oneTwoThreeFour', $formatted );

		// To lower vase without spaces > No formatter source required.
		$value = 'TEST ONE TWO';

		$targetSchema = [
			'case'      => 'lower',
			'separator' => '',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->convert( $value );

		$this->assertEquals( 'testonetwo', $formatted );

		// Camel to Snake > Formatter source required.
		$value = 'oneTwoThreeFour';

		$targetSchema = [
			'case'      => 'lower',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->convert(
			$value,
			new SlugFormatter( [ 'separator' => '' ] )
		);

		$this->assertEquals( 'one_two_three_four', $formatted );
	}
}
