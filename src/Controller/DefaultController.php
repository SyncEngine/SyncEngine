<?php

namespace App\Controller;

use App\Service\ClassFinder;
use App\Service\ModelNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Twig\Environment;
class DefaultController extends AbstractController
{
	private static $_baseEntityManager;
	private static $_classFinder;
	private static $_twig;
	private static $_router;

	public function __construct( EntityManagerInterface $entityManager, ClassFinder $classFinder, Environment $twig, UrlGeneratorInterface $router)
	{
		self::$_baseEntityManager = $entityManager;
		self::$_classFinder = $classFinder;
		self::$_twig = $twig;
		self::$_router = $router;
	}

	/**
	 * @return EntityManagerInterface|null
	 */
	public static function getEntityManager(): ?EntityManagerInterface
	{
		return self::$_baseEntityManager;
	}

	/**
	 * @return ClassFinder|null
	 */
	public static function getClassFinder(): ?ClassFinder
	{
		return self::$_classFinder;
	}

	/**
	 * @return Environment|null
	 */
	public static function getTwig(): ?Environment
	{
		return self::$_twig;
	}

	/**
	 * @return UrlGeneratorInterface|null
	 */
	public static function getRouter(): ?UrlGeneratorInterface
	{
		return self::$_router;
	}

	public function json( mixed $data, int $status = 200, array $headers = [], array $context = [] ): JsonResponse
	{
		return parent::json( ( new ModelNormalizer() )->normalize( $data ), $status, $headers, $context );
	}
}
