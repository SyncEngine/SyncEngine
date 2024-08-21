<?php

namespace SyncEngine\Service\Format;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;
use SyncEngine\Exception\InvalidValueException;
use SyncEngine\Service\Interface\FormatInterface;

class EmailFormatter extends StringFormatter implements FormatInterface
{
	const VALIDATOR_MODE = 'validator_mode';

	const VALIDATION_MODE_STRICT = Email::VALIDATION_MODE_STRICT;
	const VALIDATION_MODE_HTML5 = Email::VALIDATION_MODE_HTML5;
	const VALIDATION_MODE_HTML5_ALLOW_NO_TLD = Email::VALIDATION_MODE_HTML5_ALLOW_NO_TLD;

	private array $defaultContext = [
		/**
		 * @see Email::VALIDATION_MODES
		 */
		self::VALIDATOR_MODE => null,
	];

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		parent::__construct( $defaultContext );
	}

	public function _format( mixed $var, array $context = [] )
	{
		$var = parent::_format( $var, [ StringFormatter::TRIM => true ] );
		$var = str_replace( ' ', '', $var );

		$mode = $context[ self::VALIDATOR_MODE ] ?? $this->defaultContext[ self::VALIDATOR_MODE ];

		$constraint = new Email( mode: $mode );

		$violations = Validation::createValidator()->validate( $var, $constraint );

		if ( ! $violations->count() ) {
			return $var;
		}

		throw new InvalidValueException( 'Email validation failed: . ' . $violations );
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
			$var = $fromFormat->toString( $var );
		}

		return $this->format( $var );
	}

	public function supportsFormat( FormatInterface $format ): bool
	{
		return true;
	}
}
