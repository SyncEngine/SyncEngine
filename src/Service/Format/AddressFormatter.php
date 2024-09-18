<?php

namespace SyncEngine\Service\Format;

use SyncEngine\Service\Interface\AddressInterface;
use SyncEngine\Service\Interface\FormatInterface;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Service\ValueObject\Address;

class AddressFormatter implements FormatInterface
{
	const STRICT                     = 'strict';
	const COLUMN_COUNTRY_CODE        = 'column_country_code';
	const COLUMN_ADMINISTRATIVE_AREA = 'column_administrative_area';
	const COLUMN_LOCALITY            = 'column_locality'; // City.
	const COLUMN_DEPENDENT_LOCALITY  = 'column_dependent_locality';
	const COLUMN_POSTAL_CODE         = 'column_postal_code';
	const COLUMN_SORTING_CODE        = 'column_sorting_code';
	const COLUMN_ADDRESS_LINE_1      = 'column_address_line_1';
	const COLUMN_ADDRESS_LINE_2      = 'column_address_line_2';
	const COLUMN_ADDRESS_LINE_3      = 'column_address_line_3';
	const COLUMN_ORGANIZATION        = 'column_organization';
	const COLUMN_GIVEN_NAME          = 'column_given_name';
	const COLUMN_ADDITIONAL_NAME     = 'column_additional_name';
	const COLUMN_FAMILY_NAME         = 'column_family_name';
	const COLUMN_LOCALE              = 'column_locale';

	private array $addressProps = [
		self::COLUMN_COUNTRY_CODE        => 'countryCode',
		self::COLUMN_ADMINISTRATIVE_AREA => 'administrativeArea',
		self::COLUMN_LOCALITY            => 'locality',
		self::COLUMN_DEPENDENT_LOCALITY  => 'dependentLocality',
		self::COLUMN_POSTAL_CODE         => 'postalCode',
		self::COLUMN_SORTING_CODE        => 'sortingCode',
		self::COLUMN_ADDRESS_LINE_1      => 'addressLine1',
		self::COLUMN_ADDRESS_LINE_2      => 'addressLine2',
		self::COLUMN_ADDRESS_LINE_3      => 'addressLine3',
		self::COLUMN_ORGANIZATION        => 'organization',
		self::COLUMN_GIVEN_NAME          => 'givenName',
		self::COLUMN_ADDITIONAL_NAME     => 'additionalName',
		self::COLUMN_FAMILY_NAME         => 'familyName',
		self::COLUMN_LOCALE              => 'locale',
	];

	private array $defaultContext = [
		self::STRICT => false,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
	}

	public function sanitize( $var ): array
	{
		if ( is_object( $var ) ) {
			return ( new ModelNormalizer() )->normalize( $var );
		}
		return (array) $var;
	}

	public function getColumnContext( array $context = [] ): array
	{
		return array_intersect_key( $context ?: $this->defaultContext, $this->addressProps );
	}

	public function toAddress( $var, array $context = [] ): AddressInterface
	{
		if ( $var instanceof AddressInterface ) {
			return $var;
		}

		$context = $context ?: $this->defaultContext;
		$var     = $this->sanitize( $var );

		$address = [];

		foreach ( $this->addressProps as $key => $prop ) {
			if ( empty( $context[ $key ] ) ) {
				continue;
			}
			if ( isset( $var[ $context[ $key ] ] ) ) {
				$address[ $prop ] = $var[ $context[ $key ] ];
			}
		}

		return new Address( ...$address );
	}

	public function _format( mixed $var, array $context = [] ): array
	{
		$context = $context ?: $this->defaultContext;

		$address = $this->toAddress( $var, $context );

		if ( empty( $context[ self::STRICT ] ) ) {
			$var = $this->sanitize( $var );
		} else {
			$var = [];
		}

		$fields = array_flip( $this->addressProps );

		foreach ( $address->normalize() as $key => $value ) {
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
			if ( $fromFormat instanceof AddressFormatter ) {
				if ( empty( $this->defaultContext[ self::STRICT ] ) ) {
					// Convert to Address object using source column names.
					$address = $fromFormat->toAddress( $var );
					// Convert to new address columns using target column names.
					$address = $fromFormat->_format( $address, $this->defaultContext );
					// Remove source address keys.
					$var = array_diff_key( $var, array_flip( $fromFormat->getColumnContext() ) );
					// Combine address data while keeping any non-address fields.
					$var = array_merge( array_intersect_key( array_filter( $address ), array_flip( $this->getColumnContext() ) ), $var );
				} else {
					$var = $fromFormat->toAddress( $var );
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
