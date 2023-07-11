<?php

namespace App\Model\Trait;

trait Fields
{
	public function filterFieldsBy( $filters, $list, $strict = false ): array
	{
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
		}

		return $list;
	}
}
