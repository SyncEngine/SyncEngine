<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Entity\User;

/**
 * User editing form type, meant for Admins editing other users.
 */
class UserFormType extends AbstractType
{
	public function __construct( private readonly TranslatorInterface $translator ) {}

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
				'label' => $this->translator->trans( 'New password' ),
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				// instead of being set onto the object directly,
				// this is read and encoded in the controller
				'mapped' => false,
				'required' => false,
				'attr' => ['autocomplete' => 'new-password'],
				'constraints' => [
					new Length( min: 6, max: 4096, minMessage: 'Your password should be at least { limit } characters' ),
				],
			])
			->add( 'roles', ChoiceType::class, [
				'label'    => $this->translator->trans( 'Roles' ),
				'choices'  => [
					$this->translator->trans( 'Admin' ) => 'ROLE_ADMIN',
					$this->translator->trans( 'Editor' ) => 'ROLE_EDITOR',
					$this->translator->trans( 'Viewer' ) => 'ROLE_VIEWER',
				],
				'multiple' => true,
				'expanded' => true,
			] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => User::class,
		] );
	}
}
