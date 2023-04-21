<?php

namespace App\Form;

use App\Entity\Connection;
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
			->add('name', TextType::class)
			->add('description', TextType::class)
			->add(
				'config',
				ChoiceType::class,
				[
					'choices' => [
						'example' => 'example 1',
						'Token' => 'Token-Example',
						'URL' => '127.0.0.1',
					],
					'expanded' => true,
					'multiple' => true,
					'label' => false,
				]
			);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Connection::class,
		]);
	}
}
