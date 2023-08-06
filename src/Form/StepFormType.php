<?php

namespace App\Form;

use App\Entity\Step;
use App\Form\Type\JsonType;
use App\Model\StepModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StepFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$builder
			->add( 'name', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add( 'description', TextType::class, [
				'required' => false,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add( 'config', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'react',
					'data-type'       => 'config',
					'data-args'       => json_encode([
						'fields' => ( new StepModel( new Step() ) )->getFields()
					]),
				],
			] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => Step::class,
		] );
	}
}
