<?php

namespace SyncEngine\Service;

use Masterminds\HTML5\Parser\UTF8Utils;

class SerializationSanitizer
{
	const SANITIZE_UTF8 = 'encode_utf8';
	const SANITIZE_RESOURCE = 'sanitize_resource';

	public function sanitize( mixed $data, array $options = [] ): mixed
	{
		$options = array_merge( [
			self::SANITIZE_UTF8     => true,
			self::SANITIZE_RESOURCE => true,
		], $options );

		if ( is_iterable( $data ) ) {
			foreach ( $data as &$value ) {
				$value = $this->sanitize( $value, $options );
			}
		}

		if ( is_resource( $data ) && ! empty( $options[ self::SANITIZE_RESOURCE ] ) ) {
			$data = $this->sanitizeResource( $data );
		}

		if ( is_string( $data ) && ! empty( $options[ self::SANITIZE_UTF8 ] ) ) {
			$data = $this->encode_utf8( $data );
		}

		return $data;
	}

	public function sanitizeResource( $data )
	{
		if ( is_resource( $data ) ) {
			return sprintf(
				'resource(%d) of type (%s)',
				(int) $data,
				get_resource_type( $data )
			);
		}
		return $data;
	}

	public function encode_utf8( string $data ): string
	{
		if ( mb_detect_encoding( $data, 'UTF-8', true ) !== 'UTF-8') {
			return UTF8Utils::convertToUTF8( $data );
		}
		return $data;
	}
}
