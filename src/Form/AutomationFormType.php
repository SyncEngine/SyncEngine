<?php

namespace App\Form;

use App\Entity\Automation;
use App\Entity\Connection;
use App\Entity\Flow;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutomationFormType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('name', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			])
			->add('description', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			])
			->add('endpoint', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			])
			->add('sourceConnection', EntityType::class, [
				// looks for choices from this entity
				'class' => Connection::class,
				'choice_label' => 'name',
				'multiple' => false,
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			])
			->add('targetConnection', EntityType::class, [
				// looks for choices from this entity
				'class' => Connection::class,
				'choice_label' => 'name',
				'multiple' => false,
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			])
			->add('flow', EntityType::class, [
				// looks for choices from this entity
				'class' => Flow::class,
				'choice_label' => 'name',
				'multiple' => false,
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Automation::class,
		]);
	}
}
