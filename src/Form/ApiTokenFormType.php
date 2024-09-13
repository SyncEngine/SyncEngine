<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Entity\ApiToken;
use SyncEngine\Form\Type\JsonType;

class ApiTokenFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$builder
			->add( 'token', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add( 'description', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add( 'expires', DateTimeType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'widget'   => 'single_text',
			] )
			->add( 'config', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'react',
					'data-type'       => 'fields',
					'data-args'       => json_encode( [
						'fields' => [
							'restrictions' => [
								'label'       => 'Restrictions',
								'icon'        => 'restrict',
								'description' => 'Restrictions limit an API key’s usage and improves security',
								'nested'      => [
									'ip' => [
										'label'       => 'Restrict by IP addresses',
										'type'        => 'text',
										'placeholder' => '0.0.0.0, 0.0.0.0',
									],
									'host' => [
										'label'       => 'Restrict by host domains',
										'type'        => 'text',
										'placeholder' => 'domain.com, sub.domain.ext',
									],
								]
							]
						],
					] ),
				]
			] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => ApiToken::class,
		] );
	}
}
