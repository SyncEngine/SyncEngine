<?php

namespace SyncEngine\Tests\Column;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;
use SyncEngine\Tests\TestCase\BaseTestCase;

class SlugTest extends BaseTestCase
{
	public function testFormatSchema(): void
	{
		$var = ( new UnicodeString( 'oneTwoThree' ) );
		var_dump( ( new AsciiSlugger() )->slug( $var, ' ' )->snake()->title() );
	}
}
