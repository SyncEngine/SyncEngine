<?php

namespace App\Model\Interface;

interface Taggable
{
	public function getTags(): array;
	public function getTagsResource( array $config ): array;
}
