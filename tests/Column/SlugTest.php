<?php

namespace SyncEngine\Tests\Column;

use SyncEngine\Service\Format\SlugFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class SlugTest extends BaseTestCase
{
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
			'case' => 'lower',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'one_two_three_four', $formatted );

		// Screaming Snake Case
		$targetSchema = [
			'case' => 'upper',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'ONE_TWO_THREE_FOUR', $formatted );

		// Capitalized Snake Case
		$targetSchema = [
			'case' => 'capitalize',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'One_Two_Three_Four', $formatted );

		// Camel Case
		$targetSchema = [
			'case' => 'camel',
			'separator' => '',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'oneTwoThreeFour', $formatted );

		// Capitalized Camel Case
		$targetSchema = [
			'case' => 'capitalize',
			'separator' => '',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'OneTwoThreeFour', $formatted );

		// Namespace Case
		$targetSchema = [
			'case' => 'capitalize',
			'separator' => '/',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'One/Two/Three/Four', $formatted );

		/**
		 * Convert tests
		 */

		// Snake to Camel > No formatter source required.
		$value = 'one_two_three_four';

		$targetSchema = [
			'case' => 'camel',
			'separator' => '',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->format( $value );

		$this->assertEquals( 'oneTwoThreeFour', $formatted );

		// Camel to Snake > Formatter source required.
		$value = 'oneTwoThreeFour';

		$targetSchema = [
			'case' => 'lower',
			'separator' => '_',
		];

		$formatted = ( new SlugFormatter( $targetSchema ) )->convert( $value, new SlugFormatter( [ 'separator' => '' ] ) );

		$this->assertEquals( 'one_two_three_four', $formatted );
	}
}
