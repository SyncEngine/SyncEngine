<?php

namespace SyncEngine\Service\Format;

use Symfony\Component\String\UnicodeString;
use SyncEngine\Service\Interface\FormatInterface;

class StringFormatter implements FormatInterface
{
	const TRIM = 'trim';
	const MAX_LENGTH = 'max_length';
	const MAX_LENGTH_UNIT = 'max_length_unit';
	const ELLIPSIS = 'ellipsis';

	private array $defaultContext = [
		self::TRIM => false,
		self::MAX_LENGTH => null,
		self::MAX_LENGTH_UNIT => 'letters',
		self::ELLIPSIS => false,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
	}

	public function _format( mixed $var, array $context = [] )
	{
		if ( ! is_scalar( $var ) ) {
			return '';
		}

		// @todo Use string component?

		$var = (string) $var;

		if ( ! empty( $context[ self::TRIM ] ) ) {
			$var = trim( $var );
		}

		if ( ! empty( $context[ self::MAX_LENGTH ] ) ) {
			$length = $context[ self::MAX_LENGTH ];
			switch ( $context[ self::MAX_LENGTH_UNIT ] ?? '' ) {
				case 'words':
					$var = $this->trim_words( $var, $length, $context[ self::ELLIPSIS ] ?? false );
				break;
				default:
					$var = substr( $var, 0, $length );
				break;
			}
		}

		return $var;
	}

	public function trim_words( string $string, int $length, bool|string $ellipsis = false ): string
	{
		if ( str_word_count( $string, 0 ) > $length ) {
			$words  = str_word_count( $string, 2 );
			$pos    = array_keys( $words );
			$string = substr( $string, 0, $pos[ $length ] );

			if ( $ellipsis ) {
				if ( ! is_string( $ellipsis ) ) {
					$ellipsis = '...';
				}
				$string .= $ellipsis;
			} else {
				$string = rtrim( $string, ' ' );
			}
		}

		return $string;
	}

	/**
	 * @param  mixed                 $var
	 * @param  FormatInterface|null  $fromFormat
	 *
	 * @return mixed
	 */
	public function convert( mixed $var, ?FormatInterface $fromFormat = null ): mixed
	{
		if ( $fromFormat instanceof FormatInterface ) {
			return $fromFormat->toString( $var );
		}

		return $this->format( $var );
	}

	/**
	 * @param  mixed  $var
	 *
	 * @return string
	 */
	public function format( mixed $var ): mixed
	{
		$value = $this->_format( $var );

		if ( ! is_string( $value ) ) {
			$value = (string) $value;
		}

		return $value;
	}

	public function toLength( mixed $var ): int
	{
		return ( new UnicodeString( $var ) )->length();
	}

	public function toUnicode( mixed $var ): UnicodeString
	{
		return new UnicodeString( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return (int) $this->format( $var );
	}

	public function toFloat( mixed $var ): ?float
	{
		return (float) $this->format( $var );
	}

	public function toString( mixed $var ): ?string
	{
		return $this->format( $var );
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->format( $var ) );
	}

	public function toArray( mixed $var ): ?array
	{
		return (array) $this->format( $var );
	}

	public function toObject( mixed $var ): ?object
	{
		return (object) $this->format( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
