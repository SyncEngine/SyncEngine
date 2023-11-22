<?php

namespace App\Tests\Service;


use App\Entity\Dataset;
use App\Model\DatasetModel;
use App\Service\TagExtractor;
use App\Service\TagParser;
use PHPUnit\Framework\TestCase;

class TagExtractorTest extends TestCase
{
	// Space changes are made deliberately.
	public $stringSingle = '{{ one.two.three }}';
	public $stringMulti = 'Test {{ one.two.three }} How about some {{foo.bar}}';
	public $stringMultiSimilar = 'Test {{ one.two.three }} How about some {{foo.bar}} and {{ one.two.four}}';
	public $arraySimple = [
		'one' => 'two',
		'three' => '{{ one.two.three }}',
		'foo' => '{{ foo.bar }}',
		'more' => 'Test {{ one.two.three }} How about some {{foo.bar}} and {{ one.two.four}}',
	];
	public $arrayTraverse = [
		'one' => 'two',
		'three' => '{{ one.two.three }}',
		'foo' => [
			'foo' => '{{ foo.bar }}',
			'more' => 'Test {{ one.two.three }} How about some {{foo.bar}} and {{ one.two.four}}',
		],
	];

	public function getTagExtractor( $resource = null ): TagExtractor
	{
		return new TagExtractor();
	}

	public function testHasTag()
	{
		$extractor = $this->getTagExtractor();

		/**
		 * Any tag.
		 */

		// No tags.
		$this->assertFalse( $extractor->hasTag( 'Check one two' ) );
		$this->assertFalse( $extractor->hasTag( 'Check {{ invalid tag' ) );
		$this->assertFalse( $extractor->hasTag( 'Check invalid tag }}' ) );
		$this->assertFalse( $extractor->hasTag( [ 'one' => 'two', 'check' => '{{ invalid tag', ] ) );

		// Valid tags.
		$this->assertTrue( $extractor->hasTag( $this->stringSingle ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMulti ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMultiSimilar ) );
		$this->assertTrue( $extractor->hasTag( $this->arraySimple ) );
		$this->assertTrue( $extractor->hasTag( $this->arrayTraverse ) );

		/**
		 * Specific tags.
		 */

		// Depth 1 so no tag found.
		$search = 'two';
		$this->assertFalse( $extractor->hasTag( $this->stringSingle, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->stringMulti, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->stringMultiSimilar, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->arraySimple, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->arrayTraverse, $search ) );

		// Depth 1 so no tag found.
		$search = 'bar';
		$this->assertFalse( $extractor->hasTag( $this->stringSingle, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->stringMulti, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->stringMultiSimilar, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->arraySimple, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->arrayTraverse, $search ) );

		$search = 'one.two';
		$this->assertTrue( $extractor->hasTag( $this->stringSingle, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMulti, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMultiSimilar, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arraySimple, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arrayTraverse, $search ) );

		$search = 'one.two.three';
		$this->assertTrue( $extractor->hasTag( $this->stringSingle, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMulti, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMultiSimilar, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arraySimple, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arrayTraverse, $search ) );

		$search = 'one.two.four';
		$this->assertFalse( $extractor->hasTag( $this->stringSingle, $search ) );
		$this->assertFalse( $extractor->hasTag( $this->stringMulti, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMultiSimilar, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arraySimple, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arrayTraverse, $search ) );

		$search = 'foo';
		$this->assertFalse( $extractor->hasTag( $this->stringSingle, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMulti, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMultiSimilar, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arraySimple, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arrayTraverse, $search ) );

		$search = 'foo.bar';
		$this->assertFalse( $extractor->hasTag( $this->stringSingle, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMulti, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->stringMultiSimilar, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arraySimple, $search ) );
		$this->assertTrue( $extractor->hasTag( $this->arrayTraverse, $search ) );
	}

	public function testExtractTags()
	{
		$extractor = $this->getTagExtractor();

		/**
		 * Any tag.
		 */

		// Valid tags.
		$this->assertEquals(
			[ 'one.two.three' ],
			$extractor->extractTags( $this->stringSingle )
		);
		$this->assertEquals(
			[ 'one.two.three', 'foo.bar' ],
			$extractor->extractTags( $this->stringMulti )
		);
		$this->assertEquals(
			[ 'one.two.three', 'foo.bar', 'one.two.four' ],
			$extractor->extractTags( $this->stringMultiSimilar )
		);
		$this->assertEquals(
			[ 'one.two.three', 'foo.bar', 'one.two.three', 'foo.bar', 'one.two.four' ],
			$extractor->extractTags( $this->arraySimple )
		);
		$this->assertEquals(
			[ 'one.two.three', 'foo.bar', 'one.two.three', 'foo.bar', 'one.two.four' ],
			$extractor->extractTags( $this->arrayTraverse )
		);

		/**
		 * Specific tags.
		 */

		// Depth 1 so no tag found.
		$search = 'two';
		$this->assertEquals( [], $extractor->extractTags( $this->stringSingle, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->stringMulti, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->stringMultiSimilar, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->arraySimple, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->arrayTraverse, $search ) );

		// Depth 1 so no tag found.
		$search = 'bar';
		$this->assertEquals( [], $extractor->extractTags( $this->stringSingle, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->stringMulti, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->stringMultiSimilar, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->arraySimple, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->arrayTraverse, $search ) );

		$search = 'one.two';
		$this->assertEquals( [ 'one.two.three' ], $extractor->extractTags( $this->stringSingle, $search ) );
		$this->assertEquals( [ 'one.two.three' ], $extractor->extractTags( $this->stringMulti, $search ) );
		$this->assertEquals(
			[ 'one.two.three', 'one.two.four' ],
			$extractor->extractTags( $this->stringMultiSimilar, $search )
		);
		$this->assertEquals(
			[ 'one.two.three', 'one.two.three', 'one.two.four' ],
			$extractor->extractTags( $this->arraySimple, $search )
		);
		$this->assertEquals(
			[ 'one.two.three', 'one.two.three', 'one.two.four' ],
			$extractor->extractTags( $this->arrayTraverse, $search )
		);

		$search = 'one.two.three';
		$this->assertEquals( [ 'one.two.three' ], $extractor->extractTags( $this->stringSingle, $search ) );
		$this->assertEquals( [ 'one.two.three' ], $extractor->extractTags( $this->stringMulti, $search ) );
		$this->assertEquals( [ 'one.two.three' ], $extractor->extractTags( $this->stringMultiSimilar, $search ) );
		$this->assertEquals(
			[ 'one.two.three', 'one.two.three' ],
			$extractor->extractTags( $this->arraySimple, $search )
		);
		$this->assertEquals(
			[ 'one.two.three', 'one.two.three' ],
			$extractor->extractTags( $this->arrayTraverse, $search )
		);

		$search = 'one.two.four';
		$this->assertEquals( [], $extractor->extractTags( $this->stringSingle, $search ) );
		$this->assertEquals( [], $extractor->extractTags( $this->stringMulti, $search ) );
		$this->assertEquals( [ 'one.two.four' ], $extractor->extractTags( $this->stringMultiSimilar, $search ) );
		$this->assertEquals( [ 'one.two.four' ], $extractor->extractTags( $this->arraySimple, $search ) );
		$this->assertEquals( [ 'one.two.four' ], $extractor->extractTags( $this->arrayTraverse, $search ) );

		$search = 'foo';
		$this->assertEquals( [], $extractor->extractTags( $this->stringSingle, $search ) );
		$this->assertEquals( [ 'foo.bar' ], $extractor->extractTags( $this->stringMulti, $search ) );
		$this->assertEquals( [ 'foo.bar' ], $extractor->extractTags( $this->stringMultiSimilar, $search ) );
		$this->assertEquals( [ 'foo.bar', 'foo.bar' ], $extractor->extractTags( $this->arraySimple, $search ) );
		$this->assertEquals( [ 'foo.bar', 'foo.bar' ], $extractor->extractTags( $this->arrayTraverse, $search ) );

		$search = 'foo.bar';
		$this->assertEquals( [], $extractor->extractTags( $this->stringSingle, $search ) );
		$this->assertEquals( [ 'foo.bar' ], $extractor->extractTags( $this->stringMulti, $search ) );
		$this->assertEquals( [ 'foo.bar' ], $extractor->extractTags( $this->stringMultiSimilar, $search ) );
		$this->assertEquals( [ 'foo.bar', 'foo.bar' ], $extractor->extractTags( $this->arraySimple, $search ) );
		$this->assertEquals( [ 'foo.bar', 'foo.bar' ], $extractor->extractTags( $this->arrayTraverse, $search ) );
	}
}
