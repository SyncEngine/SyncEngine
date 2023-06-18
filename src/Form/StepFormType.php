<?php

namespace App\Form;

use App\Entity\Step;
use App\Form\Type\JsonType;
use App\Service\TaskService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StepFormType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$tasks = TaskService::getTasks();
		foreach ( $tasks as $type => $task ) {
			$tasks[ $type ] = $task->getArgs();
		}

		$builder
			->add('name', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			])
			->add('description', TextType::class, [
				'required' => false,
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			])
			->add('config', JsonType::class, [
				'attr' => [
					'data-controller' => 'config',
					'data-type' => 'step',
					'data-args' => json_encode([
						'taskTypes' => $tasks
					]),
				],
				'row_attr' => [
					'class' => 'form-floating mb-2',
				],
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Step::class,
		]);
	}
}
