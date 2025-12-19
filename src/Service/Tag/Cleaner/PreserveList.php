<?php

namespace SyncEngine\Service\Tag\Cleaner;

class PreserveList extends TagCleaner
{

	public function shouldClean( string $tag ): bool
	{
		if ( empty( $this->context ) ) {
			return true;
		}

		$tagParts     = $this->extractor->getTagParts( $tag );
		$preserveList = $this->context;
		$preserved    = false;

		foreach ( $tagParts as $tagPart ) {
			if ( ! is_array( $preserveList ) ) {
				if ( $tagPart === $preserveList ) {
					$preserved = true;
				}
				break;
			}
			if ( empty( $preserveList ) ) {
				// Empty whitelist key means all subs are allowed.
				$preserved = true;
				break;
			}
			if ( in_array( $tagPart, $preserveList, true ) ) {
				$preserved = true;
				break;
			}
			if ( ! isset( $preserveList[ $tagPart ] ) ) {
				break;
			}
			if ( ! is_iterable( $preserveList[ $tagPart ] ) ) {
				$preserved = true;
				break;
			}
			$preserveList = $preserveList[ $tagPart ];
		}

		return ! $preserved;
	}
}
