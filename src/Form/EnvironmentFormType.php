<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvironmentFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$builder
			->add('APP_ENV', ChoiceType::class, [
				'label' => 'Environment',
				'required' => true,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => [
					'Development' => 'dev',
					'Production' => 'prod',
				],
			])
			->add('APP_SECRET', PasswordType::class, [
				'label' => 'Secret',
				'required' => false,
				'attr' => [
					'placeholder' => 'Change your app secret. Will autogenerate if empty.',
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			/*->add('database', ChoiceType::class, [
				'label' => 'Database Type',
				'required' => true,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => [
					'sqlite' => 'SQLite',
					'mysql'  => 'MySQL',
				],
			])*/
			->add('DATABASE_URL', TextType::class, [
				'label' => 'Database URL',
				'required' => true,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			]);
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [] );
	}
}
