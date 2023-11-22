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
}
