<?php

namespace App\Controller;

use App\Entity\Step;
use App\Entity\Flow;
use App\Form\StepFormType;
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

	#[Route('/step/unlink/{step}-{flow}', name: 'unlink_step')]
	public function unlink(Step $step,Flow $flow, Request $request, EntityManagerInterface $entityManager): Response
	{
		$flows = $entityManager->getRepository(Flow::class)->findAll();
		$delete = 1;
		foreach ($flows as $flw){
			if ($flw != $flow and in_array($step->getId(), $flw->getSteps()))
				{
					$this->addFlash('warning', 'Step is also found in flow: '.$flw->getName());
					$delete = 0;
				}
		}

		$flow->setSteps(array_diff( $flow->getSteps(), [$step->getId()]));
		$entityManager->persist($flow);
		$entityManager->flush();
		$this->addFlash('success', 'Succesfully unlinked step from flow: '.$flow->getName());

		if ($delete) {
			$entityManager->remove($step);
			$entityManager->flush();
			$this->addFlash('success', 'Succesfully deleted step');
		}

		$route = $request->headers->get('referer');
		return $this->redirect($route);
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
			$class_name = "App\Controller\Task\\" . ucfirst($config['type']);
			if (class_exists($class_name)) {
				$task = new $class_name();
				$data = $task->execute($config, $data);
			}
		}
		return $data;
	}

	public function executeModule(string $moduleName, array $config, array $data): array
	{
		return ModulesController::getModule($moduleName)->executeConfig($config, $data);
	}
}
