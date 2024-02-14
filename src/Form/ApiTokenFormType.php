<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Entity\ApiToken;

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
			] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => ApiToken::class,
		] );
	}
}
