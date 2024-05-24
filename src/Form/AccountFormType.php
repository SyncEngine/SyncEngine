<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use SyncEngine\Entity\User;

class AccountFormType extends AbstractType
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
				'help' => 'Leave empty to keep current password.',
				'label' => 'New Password',
				// instead of being set onto the object directly,
				// this is read and encoded in the controller
				'mapped' => false,
				'attr' => ['autocomplete' => 'new-password'],
				'constraints' => [
					new Length([
						'min' => 6,
						'minMessage' => 'Your password should be at least { limit } characters',
						// max length allowed by Symfony for security reasons
						'max' => 4096,
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
