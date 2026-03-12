<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use SyncEngine\Entity\User;

class RegistrationFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$builder
			->add('email', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('name', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('plainPassword', PasswordType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				// instead of being set onto the object directly,
				// this is read and encoded in the controller
				'mapped' => false,
				'attr' => ['autocomplete' => 'new-password'],
				'constraints' => [
					new NotBlank([
						'message' => 'Please enter a password',
					]),
					new Length([
						'min' => 6,
						'minMessage' => 'Your password should be at least { limit } characters',
						// max length allowed by Symfony for security reasons
						'max' => 4096,
					]),
				],
			])
			->add('agreeTerms', CheckboxType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'label' => '<a href="https://syncengine.io/license" target="_blank">I accept the license terms and conditions</a>',
				'label_html' => true,
				'mapped' => false,
				'constraints' => [
					new IsTrue([
						'message' => 'You should agree to our license terms.',
					]),
				],
			]);
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => User::class,
		] );
	}
}
