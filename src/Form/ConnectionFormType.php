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
							'test' => []
						]
					]),
				]
			] )
			->add('authType', ChoiceType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
				'choices'  => [
					'None' => 'None',
					'Basic auth' => "Basic auth",
					'API Key' => "API Key",
					'FTP' => "FTP",
					'SFTP' => "SFTP",
					'Bearer Token' => "Bearer Token"
				],
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Connection::class,
		]);
	}
}
