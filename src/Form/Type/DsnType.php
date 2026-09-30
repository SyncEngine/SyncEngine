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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Form\Fields\Collection\FieldCollection;

/**
 * @implements DataTransformerInterface<mixed, mixed>
 */
class DsnType extends TextType
{
	public function __construct(
		protected readonly TranslatorInterface $translator,
		protected readonly ParameterBagInterface $parameterBag,
	) {}

	public function dsnFields(): FieldCollection
	{
		return new FieldCollection( [
			'protocol' => [ 'label' => $this->translator->trans( 'Protocol' ), 'customizable' => true ],
			'username' => [ 'label' => $this->translator->trans( 'Username' ) ],
			'password' => [ 'label' => $this->translator->trans( 'Password' ), 'type' => 'password' ],
			'host'     => [ 'label' => $this->translator->trans( 'Host' ) ],
			'port'     => [ 'label' => $this->translator->trans( 'Port' ), 'type' => 'number' ],
			'path'     => [ 'label' => $this->translator->trans( 'Path' ) ],
			'query'    => [ 'label' => $this->translator->trans( 'Parameters' ), 'type' => 'params', 'collapsed' => true ],
		] );
	}

	public function getBlockPrefix(): string
	{
		return $this->parameterBag->get( 'kernel.debug' ) ? 'text' : 'password';
	}

	public function getProtocols(): array
	{
		return [];
	}

	public function dsnDefaults(): array
	{
		return [];
	}


	public function buildFieldArgs( OptionsResolver $resolver, array $attr ): array
	{
		$fields = $this->dsnFields();
		$protocols = $this->getProtocols();

		if ( $protocols ) {
			$fields['protocol']['choices'] = $protocols;
			$fields['protocol']['type'] = 'select';
		}

		return array_merge_recursive( $attr, [
			'data-controller' => 'react',
			'data-type'       => 'dsn',
			'data-args'       => json_encode( [
				'fields'   => $fields->normalize(),
				'defaults' => $this->dsnDefaults(),
			] ),
		] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		parent::configureOptions( $resolver );

		$resolver->setNormalizer( 'attr', fn( $resolver, $attr ) => $this->buildFieldArgs( $resolver, $attr ) );
	}
}
