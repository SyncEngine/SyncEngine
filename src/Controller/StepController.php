<?php

namespace App\Controller;

use App\Form\StepFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Step;

class StepController extends AbstractController
{
	public function ExecuteStep(Step $step, $datafields)
	{
		if(isset($step->getConfig()["module"])){
			$moduleController = new ModuleController();
			$datafields = $moduleController->executeModuleStep( $step->getConfig()["module"],$step->getConfig(), $datafields);
		}
		return $datafields;
	}
}
