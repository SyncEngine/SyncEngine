<?php

namespace App\Form;

use App\Controller\DefaultController;
use App\Controller\ModulesController;
use App\Entity\Step;
use App\Form\Type\JsonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StepFormType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$defCont = new DefaultController();

		$tasks = $defCont->getClassesInNamespace("App\Controller\Task");
		$taskTypes = [];

		foreach ($tasks as $class) {
			$task = new $class;
			$taskTypes[$task->getType()] = $task->getArgs();
		}

		$modulesCont = new ModulesController();

		$modules = $modulesCont->getModules();
		foreach ( $modules as $module ) {
			$tasks = $module->getTasks();
			foreach ( $tasks as $task ) {
				$taskTypes[ $task->getType() ] = $task->getArgs();
			}
		}

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
			->add('config', JsonType::class, [
				'attr' => [
					'data-controller' => 'config',
					'data-type' => 'step',
					'data-args' => json_encode([
						'taskTypes' => $taskTypes
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
