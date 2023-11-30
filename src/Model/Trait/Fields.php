<?php

namespace App\Model\Trait;

trait Fields
{
	public function filterFieldsBy( $filters, $list, $strict = false, bool|string|array $clean = false ): array
	{
		if ( true === $clean ) {
			$clean = array_keys( $filters );
		}

		foreach ( $list as $index => $item ) {
			foreach ( $filters as $key => $filter ) {
				if ( $strict ) {
					if ( ! isset( $item[ $key ] ) || $item[ $key ] !== $filter ) {
						unset( $list[ $index ] );
					}
				} else {
					if ( ! empty( $item[ $key ] ) && $item[ $key ] !== $filter ) {
						unset( $list[ $index ] );
					}
				}
			}

			if ( $clean ) {
				foreach ( (array) $clean as $key ) {
					unset( $list[ $index ][ $key ] );
				}
			}
		}

		return $list;
	}
}
