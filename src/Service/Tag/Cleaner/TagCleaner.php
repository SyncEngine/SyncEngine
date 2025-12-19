<?php

namespace SyncEngine\Service\Tag\Cleaner;

use SyncEngine\Service\Tag\TagExtractor;

class TagCleaner implements CleanerInterface
{
	public function __construct(
		protected iterable $context = [],
		protected ?TagExtractor $extractor = null,
	) {
		if ( $this->extractor === null ) {
			$this->extractor = new TagExtractor();
		}
	}

	public function shouldClean( string $tag ): bool
	{
		return true;
	}
}
