<?php

namespace App\Controller;

use App\Service\ClassFinder;
use App\Service\ModelNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController
{
	private static $_baseEntityManager;
	private static $_classFinder;
	private static $_container;

	protected string $defaultDomain = 'core';

	public static function get( $name ): mixed
	{
		if ( 'entity_manager' === $name ) {
			return self::$_baseEntityManager;
		}
		return self::$_container->get( $name );
	}

	public static function getSubscribedServices(): array
	{
		return array_merge(
			parent::getSubscribedServices(),
			[
				'translator' => '?'.TranslatorInterface::class,
			]
		);
	}

	protected function trans( ?string $id, array $parameters = [], string $domain = null, string $locale = null ): string {
		return $this->container->get('translator')->trans( $id, $parameters, $domain ?? $this->defaultDomain, $locale );
	}

	public function __construct( EntityManagerInterface $entityManager, ClassFinder $classFinder )
	{
		self::$_baseEntityManager = $entityManager;
		self::$_classFinder = $classFinder;
	}

	#[Required]
	public function setContainer( ContainerInterface $container ): ?ContainerInterface
	{
		$previous = parent::setContainer( $container );

		if ( ! self::$_container ) {
			self::$_container = $this->container;
		}

		return $previous;
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

	public function json( mixed $data, int $status = 200, array $headers = [], array $context = [] ): JsonResponse
	{
		return parent::json( ( new ModelNormalizer() )->normalize( $data ), $status, $headers, $context );
	}
}
