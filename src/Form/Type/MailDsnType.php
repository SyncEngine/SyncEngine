<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SyncEngine\Form\Type;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Form\Fields\Collection\FieldCollection;

/**
 * @implements DataTransformerInterface<mixed, mixed>
 */
class MailDsnType extends DsnType
{
	public function dsnFields(): FieldCollection
	{
		$fields = parent::dsnFields();

		$fields->remove( 'query' );

		return $fields;
	}

	public function getProtocols(): array
	{
		return [
			[ 'label' => 'SMTP', 'value' => 'smtp' ],
			[ 'label' => 'Sendmail', 'value' => 'sendmail' ],
			//[ 'label' => 'Postmark', 'value' => 'postmark' ],
			//[ 'label' => 'Gmail', 'value' => 'gmail' ],
			//[ 'label' => 'Amazon SES', 'value' => 'ses' ],
		];
	}

	public function dsnDefaults(): array
	{
		return  [
			'port' => [
				'smtp'     => 587,
				'sendmail' => null,
			],
			'query' => [
				'charset' => 'utf8',
			],
		];
	}
}
