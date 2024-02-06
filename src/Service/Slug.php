<?php

namespace SyncEngine\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

class Slug
{
	public function slugify( string $text ): string
	{
		return ( new AsciiSlugger() )->slug( $text );
	}
}
