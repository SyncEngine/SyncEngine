<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Service\System;

class EnvironmentFormType extends AbstractType
{
	public function __construct( private readonly System $system ) {}

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

		$builder
			->add('APP_ENV', ChoiceType::class, [
				'label' => 'Environment',
				'required' => true,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => [
					'Production' => 'prod',
					'Development' => 'dev',
				],
			])
			->add( 'APP_DEBUG', ChoiceType::class, [
				'label' => 'Debug',
				'disabled' => 'dev' === $this->system->getEnv()->get( 'APP_ENV' ),
				'required' => false,
				'choices' => [
					'Enabled' => '1',
					'Disabled' => '0',
				],
				'attr' => [
					'placeholder' => 'Change your app secret. Will autogenerate if empty.',
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
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
			->add('DATABASE_URL', TextType::class, [
				'label' => 'Database',
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
				'label' => 'Mailer',
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
				'label' => 'Email sender',
				'required' => false,
				'attr' => [
					'placeholder' => 'webmaster@yourdomain.com'
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MAILER_EMAIL_ADMIN', EmailType::class, [
				'label' => 'Send email logs to',
				'required' => false,
				'attr' => [
					'placeholder' => 'webmaster@yourdomain.com'
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_TRUSTED_IPS', TextType::class, [
				'label' => 'Restrict everything by IP',
				'help'  => 'Separate multiple IP addresses by comma, admin IP addresses are included.',
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
