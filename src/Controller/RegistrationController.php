<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;


class RegistrationController extends DefaultController
{
	#[Route('/register', name: 'app_register')]
	public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
	{
		$existingUsers = $entityManager->getRepository(User::class)->findAll();
		if ($existingUsers) {
			$this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');
		}
		$user = new User();
		$form = $this->createForm(RegistrationFormType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$user->setPassword(
				$userPasswordHasher->hashPassword(
					$user,
					$form->get('plainPassword')->getData()
				)
			);

			$role = ($existingUsers) ? ["ROLE_USER"] : ["ROLE_ADMIN"];
			$user->setRoles($role);

			$entityManager->persist($user);
			$entityManager->flush();

			return $this->redirectToRoute('app_index');
		}

		return $this->render('registration/register.html.twig', [
			'registrationForm' => $form,
		]);
	}
}
