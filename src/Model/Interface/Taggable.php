<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Service\ExecutionContext;

interface Taggable
{
	public function getTags(): array;
	public function getTagsResource( array $config = [], ?ExecutionContext $context = null ): array;
}
