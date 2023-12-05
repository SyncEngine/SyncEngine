<?php

namespace SyncEngine\Model\Interface;

interface Taggable
{
	public function getTags(): array;
	public function getTagsResource( array $config ): array;
}
