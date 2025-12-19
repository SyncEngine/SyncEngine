<?php

namespace SyncEngine\Service\Tag\Cleaner;

class DiscardList extends TagCleaner
{
	public function shouldClean( string $tag ): bool
	{
		if ( empty( $this->context ) ) {
			return false;
		}

		$tagParts    = $this->extractor->getTagParts( $tag );
		$discardList = $this->context;
		$discard     = false;

		foreach ( $tagParts as $tagPart ) {
			if ( ! is_iterable( $discardList ) ) {
				if ( ! empty( $discardList ) ) {
					$discard = true;
				}
				break;
			}
			if ( in_array( $tagPart, (array) $discardList, true ) ) {
				return true;
			}
			if ( ! isset( $discardList[ $tagPart ] ) ) {
				break;
			}
			$discardList = $discardList[ $tagPart ];
		}

		return $discard;
	}
}
