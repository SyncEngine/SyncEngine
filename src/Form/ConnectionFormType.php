<?php

namespace App\Form;

use App\Entity\Connection;
use App\Form\Type\JsonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectionFormType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('name', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			] )
			->add('description', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			] )
			->add( 'config', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
				'attr' => [
					'data-controller' => 'config',
					'data-type'       => 'connection',
					'data-args'       => json_encode([
						'fields' => [
							'auth_type' => [
								'label' => 'Auth type',
								'type' => 'select',
								'choices'  => [
									'none' => 'None',
									'basic' => "Basic auth",
									'key' => "API Key",
									'ftp' => "FTP",
									'sftp' => "SFTP",
									'token' => "Bearer Token",
								],
							],
							'host' => [
								'label' => 'Host / Base URL',
								'type' => 'text',
								'conditionals' => [
									'auth_type' => [ 'operator' => 'isset' ]
								],
							],
							'port' => [
								'label' => 'Port',
								'type' => 'text',
								'conditionals' => [
									'auth_type' => [ 'operator' => 'isset' ]
								],
							],
							'key' => [
								'label' => 'Key / SSH Key',
								'type' => 'password',
								'conditionals' => [
									'auth_type' => [ 'operator' => 'isset' ]
								],
							],
							'secret' => [
								'label' => 'Secret / SSH Secret',
								'type' => 'password',
								'conditionals' => [
									'auth_type' => [ 'operator' => 'isset' ]
								],
							],
							'username' => [
								'label' => 'Username',
								'type' => 'text',
								'conditionals' => [
									'auth_type' => [ 'operator' => 'isset' ]
								],
							],
							'password' => [
								'label' => 'Password',
								'type' => 'password',
								'conditionals' => [
									'auth_type' => [ 'operator' => 'isset' ]
								],
							],
							'token' => [
								'label' => 'Token',
								'type' => 'password',
								'conditionals' => [
									'auth_type' => [ 'operator' => 'isset' ]
								],
							],
						]
					]),
				]
			] );
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Connection::class,
		]);
	}
}
