<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Service\System;

class EnvironmentFormType extends AbstractType
{
	public function __construct(
		private readonly System $system,
		private readonly TranslatorInterface $translator,
	) {}

	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$dsnFields = [
			'protocol' => [],
			'username' => [ 'conditions' => [ 'protocol' => [ 'operator' => '!=' , 'compare' => 'sqlite' ] ] ],
			'password' => [ 'conditions' => [ 'protocol' => [ 'operator' => '!=' , 'compare' => 'sqlite' ] ] ],
			'host' => [ 'conditions' => [ 'protocol' => [ 'operator' => '!=' , 'compare' => 'sqlite' ] ] ],
			'port' => [ 'conditions' => [ 'protocol' => [ 'operator' => '!=' , 'compare' => 'sqlite' ] ] ],
			'path' => [],
		];

		$mode = $this->system->getEnv()->get( 'APP_ENV' );

		$builder
			->add('APP_ENV', ChoiceType::class, [
				'label' => $this->translator->trans( 'Environment' ),
				'required' => true,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => [
					$this->translator->trans( 'Production' ) => 'prod',
					$this->translator->trans( 'Development' ) => 'dev',
				],
			])
			->add( 'APP_DEBUG', ChoiceType::class, [
				'label' => $this->translator->trans( 'Debug' ),
				'disabled' => 'dev' === $mode,
				'required' => false,
				'choices' => [
					$this->translator->trans( 'Disabled' ) => '0',
					$this->translator->trans( 'Enabled' ) => '1',
				],
				'attr' => [
					'placeholder' => $this->translator->trans( 'Change your app secret. Will autogenerate if empty.' ),
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add('APP_SECRET', PasswordType::class, [
				'label' => $this->translator->trans( 'Secret' ),
				'required' => false,
				'attr' => [
					'placeholder' => $this->translator->trans( 'Change your app secret. Will autogenerate if empty.' ),
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('DATABASE_URL', TextType::class, [
				'label' => $this->translator->trans( 'Database' ),
				'required' => true,
				'attr' => [
					'placeholder'     => 'sql://user:pass@host:port/database',
					'data-controller' => 'react',
					'data-type'       => 'dsn',
					'data-args'       => json_encode( [
						'fields' => $dsnFields,
					] ),
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('MAILER_DSN', TextType::class, [
				'label' => $this->translator->trans( 'Mailer' ),
				'required' => false,
				'attr' => [
					'placeholder' => 'smtp://user:pass@smtp.example.com:port',
					'data-controller' => 'react',
					'data-type'       => 'dsn',
					'data-args'       => json_encode( [
						'fields' => $dsnFields,
					] ),
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MAILER_SENDER', EmailType::class, [
				'label' => $this->translator->trans( 'Email sender' ),
				'required' => false,
				'attr' => [
					'placeholder' => 'webmaster@yourdomain.com'
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MAILER_EMAIL_ADMIN', EmailType::class, [
				'label' => $this->translator->trans( 'Send email logs to' ),
				'required' => false,
				'attr' => [
					'placeholder' => 'webmaster@yourdomain.com'
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_TRUSTED_IPS', TextType::class, [
				'label' => $this->translator->trans( 'Restrict everything by IP' ),
				'help'  => $this->translator->trans( 'Separate multiple IP addresses by comma, admin IP addresses are included.' ),
				'required' => false,
				'attr' => [
					'placeholder' => '0.0.0.0, 0.0.0.0'
				],
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
