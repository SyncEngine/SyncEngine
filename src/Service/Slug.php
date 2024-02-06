<?php

namespace SyncEngine\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

class Slug
{
	public function slugify( string $text, string $separator = '-', string $locale = null ): string
	{
		return strtolower( ( new AsciiSlugger() )->slug( $text, $separator, $locale ) );
	}
}
