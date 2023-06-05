<?php

namespace App\Controller;

use App\Entity\Step;
use App\Form\StepFormType;
use App\Service\ModuleService;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StepController extends AbstractController
{
	#[Route('/step/edit/{id}', name: 'edit_step')]
	public function edit(Step $step, Request $request, EntityManagerInterface $entityManager): Response
	{
		$form = $this->createForm(StepFormType::class, $step);
		$form->add('save', SubmitType::class, ['label' => 'Update']);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {

			$entityManager->persist($step);
			$entityManager->flush();

			$this->addFlash('success', 'Successfully edited step!');

			return $this->redirectToRoute('app_index');
		}


		return $this->render('step/edit.html.twig', [
			'form' => $form,
		]);
	}

	public function executeStep(Step $step, $data)
	{
		$config = $step->getConfig();

		$data = $this->executeConfig($config, $data);

		return $data;
	}

	public function executeConfig(array $config, $data): array
	{
		$tasks = $config['tasks'] ?? [];
		if ($tasks) {
			foreach ($tasks as $task) {
				if (!empty($task['module'])) {
					$data = $this->executeModule($task["module"], $task, $data);
				} else {
					$data = $this->executeTask($task, $data);
				}
			}
		}
		return $data;
	}

	public function executeTask(array $config, $data): array
	{
		$task = $config['type'] ?? '';
		if ($task) {
			$task = ( new TaskService() )->getTask( $task );
			if ( $task ) {
				$data = $task->execute($config, $data);
			}
		}
		return $data;
	}

	public function executeModule(string $moduleName, array $config, array $data): array
	{
		return ModuleService::getModule($moduleName)->executeConfig($config, $data);
	}
}
