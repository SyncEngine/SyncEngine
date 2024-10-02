<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\FormatInterface;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Service\ValueObject\File;

class FileFormatter implements FormatInterface
{
	const STRICT           = 'strict';
	const COLUMN_NAME      = 'column_name';
	const COLUMN_EXTENSION = 'column_extension';
	const COLUMN_URL       = 'column_url';
	const COLUMN_MIMETYPE  = 'column_mimetype';
	const COLUMN_SIZE      = 'column_size';
	const COLUMN_BASE64    = 'column_base64';

	private array $fileProps = [
		self::COLUMN_NAME      => 'name',
		self::COLUMN_EXTENSION => 'extension',
		self::COLUMN_URL       => 'url',
		self::COLUMN_MIMETYPE  => 'mimeType',
		self::COLUMN_SIZE      => 'size',
		self::COLUMN_BASE64    => 'base64',
	];

	private array $defaultContext = [
		self::STRICT => false,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
	}

	public function getColumnContext( array $context = [] ): array
	{
		return array_intersect_key( $context ?: $this->defaultContext, $this->fileProps );
	}

	protected function isBinary( string $var ): bool
	{
		return ! mb_check_encoding( $var, 'UTF-8' );
		//return ! preg_match( '//u',  $var );
	}

	public function sanitize( $var ): array
	{
		if ( ! is_scalar( $var ) ) {
			if ( $var instanceof File ) {
				return $var->normalize();
			}

			if ( is_object( $var ) ) {
				return ( new ModelNormalizer() )->normalize( $var );
			}

			return (array) $var;
		}

		if ( $this->isBinary( $var ) ) {
			return [
				self::COLUMN_BASE64 => base64_encode( $var ),
			];
		}

		if ( ! is_string( $var ) ) {
			return [];
		}

		$file = [];

		if ( str_starts_with( $var, 'data:' ) ) {
			$var   = substr( $var, 5 );
			$parts = explode( ';', $var, 2 );
			$var   = $parts[1];

			$file[ self::COLUMN_MIMETYPE ] = $parts[0];
		}

		if ( str_starts_with( $var, 'base64,' ) ) {
			$var = substr( $var, 7 );
		}
		$file[ self::COLUMN_BASE64 ] = $var;

		return $file;
	}

	public function toFile( mixed $var, array $context = [] ): File
	{
		if ( $var instanceof File ) {
			return $var;
		}

		$context = $context ?: $this->defaultContext;
		$var     = $this->sanitize( $var );

		$file = [];

		foreach ( $this->fileProps as $key => $prop ) {
			if ( empty( $context[ $key ] ) ) {
				continue;
			}
			if ( isset( $var[ $context[ $key ] ] ) ) {
				$file[ $prop ] = $var[ $context[ $key ] ];
			}
		}

		return new File( ...$file );
	}

	public function toBinary( mixed $var, array $context = [] ): string
	{
		if ( ! $var instanceof File ) {
			$var = $this->toFile( $var, $context );
		}

		return base64_decode( $var->getBase64() );
	}

	public function _format( mixed $var, array $context = [] ): array
	{
		$context = $context ?: $this->defaultContext;

		$file = $this->toFile( $var, $context );

		if ( empty( $context[ self::STRICT ] ) ) {
			$var = $this->sanitize( $var );
		} else {
			$var = [];
		}

		$fields = array_flip( $this->fileProps );

		foreach ( $file->normalize() as $key => $value ) {
			if ( ! isset( $fields[ $key ] ) ) {
				// @todo log?
				continue;
			}

			if ( ! empty( $context[ $fields[ $key ] ] ) ) {
				$var[ $context[ $fields[ $key ] ] ] = $value;
			}
		}

		return $var;
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
			if ( $fromFormat instanceof FileFormatter ) {
				// @todo This is the same as the Address column. Before merge this should be added to a trait.
				if ( empty( $this->defaultContext[ self::STRICT ] ) ) {
					// Convert to file object using source column names.
					$file = $fromFormat->toFile( $var );
					// Convert to new file columns using target column names.
					$file = $fromFormat->_format( $file, $this->defaultContext );
					// Remove source file keys.
					$var = array_diff_key( $var, array_flip( $fromFormat->getColumnContext() ) );
					// Combine file data while keeping any non-file fields.
					$var = array_merge(
						array_intersect_key( array_filter( $file ), array_flip( $this->getColumnContext() ) ),
						$var
					);
				} else {
					$var = $fromFormat->toFile( $var );
				}
			} else {
				$var = $fromFormat->toArray( $var );
			}
		}

		return $this->format( $var );
	}

	/**
	 * @param  mixed  $var
	 *
	 * @return mixed
	 */
	public function format( mixed $var ): mixed
	{
		return $this->_format( $var );
	}

	public function toInt( mixed $var ): ?int
	{
		return count( $this->format( $var ) );
	}

	public function toFloat( mixed $var ): ?float
	{
		return count( $this->format( $var ) );
	}

	public function toString( mixed $var ): ?string
	{
		return null;
	}

	public function toBool( mixed $var ): ?bool
	{
		return ! empty( $this->format( $var ) );
	}

	public function toArray( mixed $var ): ?array
	{
		return $this->format( $var );
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
