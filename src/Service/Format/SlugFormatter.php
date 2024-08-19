<?php

namespace SyncEngine\Service\Format;

use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;
use SyncEngine\Service\Interface\FormatInterface;

class SlugFormatter extends StringFormatter implements FormatInterface
{
	const SEPARATOR = 'separator';
	const CASE      = 'case';
	const LOCALE    = 'locale';

	private array $defaultContext = [
		self::SEPARATOR => '-',
		self::CASE      => '',
		self::LOCALE    => null,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		parent::__construct( $defaultContext );
	}

	public function toConvertable( $var, array $context = [] ): AbstractUnicodeString
	{
		$separator = $context[ self::SEPARATOR ] ?? $this->defaultContext[ self::SEPARATOR ];

		if ( ! $var instanceof AbstractUnicodeString ) {
			$var = new UnicodeString( $var );
		}

		// @todo auto-check for camelCase? ctype_alpha > Do this opt-in within column model!
		if ( ! $separator ) {
			$var = $var->snake()->replace( '_', ' ' )->title( true );
		}

		return $var;
	}

	/**
	 * @param  mixed  $var
	 * @param  array  $context
	 *
	 * @return string|\Symfony\Component\String\AbstractString|AbstractUnicodeString
	 */
	public function _format( mixed $var, array $context = [] )
	{
		$separator = $context[ self::SEPARATOR ] ?? $this->defaultContext[ self::SEPARATOR ];
		$case      = $context[ self::CASE ] ?? $this->defaultContext[ self::CASE ];
		$locale    = $context[ self::LOCALE ] ?? $this->defaultContext[ self::LOCALE ];

		$slugger = new AsciiSlugger( $locale );

		switch ( $case ) {
			case 'ucwords':
				if ( empty( $separator ) ) {
					return $slugger->slug( $var, ' ' )->camel()->title();
				}
				return $slugger->slug( $var, ' ' )->title( true )->replace( ' ', $separator );

			case 'ucfirst':
				if ( empty( $separator ) ) {
					return $slugger->slug( $var, '' )->title();
				}
				return $slugger->slug( $var, ' ' )->title()->replace( ' ', $separator );

			case 'camel':
				if ( empty( $separator ) ) {
					return $slugger->slug( $var, ' ' )->camel();
				}
				return $slugger->slug( lcfirst( $slugger->slug( $var, ' ' )->title( true ) ), $separator );

			case 'upper':
				return $slugger->slug( $var, $separator )->upper();

			case 'lower':
				return $slugger->slug( $var, $separator )->lower();

			default:
				return $slugger->slug( $var, $separator );
		}
	}

	/**
	 * @param  mixed                 $var
	 * @param  FormatInterface|null  $fromFormat
	 *
	 * @return string
	 */
	public function convert( mixed $var, ?FormatInterface $fromFormat = null ): mixed
	{
		if ( $fromFormat instanceof FormatInterface ) {
			if ( $fromFormat instanceof SlugFormatter ) {
				$var = $fromFormat->toConvertable( $var );
			} elseif ( $fromFormat instanceof StringFormatter ) {
				$var = $fromFormat->toUnicode( $var );
			} else {
				$var = $fromFormat->toString( $var );
			}
		}

		return $this->format( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
