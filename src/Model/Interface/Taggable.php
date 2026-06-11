<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Runtime\ExecuteContext;

interface Taggable
{
	public function getTags(): array;
	public function getTagsResource( array $config = [], ?ExecuteContext $context = null ): array;
}
