<?php

namespace SyncEngine\Service\Tag\Cleaner;

interface CleanerInterface
{
	public function shouldClean( string $tag ): bool;
}
