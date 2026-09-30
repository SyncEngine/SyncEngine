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

		$fields['username']['conditions'] = [ 'protocol' => [ 'operator' => 'not_in', 'compare' => [ 'sendmail', 'native' ] ] ];
		$fields['password']['conditions'] = [ 'protocol' => [ 'operator' => 'not_in', 'compare' => [ 'sendmail', 'native' ] ] ];
		$fields['host']['conditions'] = [ 'protocol' => [ 'operator' => 'not_in', 'compare' => [ 'sendmail', 'native' ] ] ];
		$fields['port']['conditions'] = [ 'protocol' => [ 'operator' => 'not_in', 'compare' => [ 'sendmail', 'native' ] ] ];
		$fields['query']['conditions'] = [ 'protocol' => [ 'operator' => 'not_in', 'compare' => [ 'sendmail', 'native' ] ] ];

		$fields['protocol']->setHelp( 'https://symfony.com/doc/current/mailer.html#using-built-in-transports' );

		return $fields;
	}

	public function getProtocols(): array
	{
		return [
			[ 'label' => 'SMTP', 'value' => 'smtp' ],
			[ 'label' => 'Sendmail', 'value' => 'sendmail' ],
			[ 'label' => 'Native', 'value' => 'native', 'description' => $this->translator->trans( 'Not recommended' ) ],
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
				'native'   => null,
			],
			'path' => [
				'smtp'     => '',
				'sendmail' => 'default',
				'native'   => 'default',
			],
		];
	}
}
