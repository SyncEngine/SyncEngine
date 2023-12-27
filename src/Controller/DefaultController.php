<?php

namespace SyncEngine\Controller;

use SyncEngine\Service\Blueprints;
use SyncEngine\Service\ClassFinder;
use SyncEngine\Service\ModelExporter;
use SyncEngine\Service\ModelImporter;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Service\Tasks;
use SyncEngine\Service\Webservices;
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

	protected string $defaultDomain = 'messages';

	public static function getContainer()
	{
		return self::$_container;
	}

	public static function get( $name ): mixed
	{
		return self::$_container->get( $name );
	}

	public static function getSubscribedServices(): array
	{
		return array_merge(
			parent::getSubscribedServices(),
			[
				'translator' => '?'.TranslatorInterface::class,
				'entitymanager' => '?'.EntityManagerInterface::class,
				'Tasks' => '?'.Tasks::class,
				'Webservices' => '?'.Webservices::class,
				'Blueprints' => '?'.Blueprints::class,
				'ModelNormalizer' => '?'.ModelNormalizer::class,
				'ModelExporter' => '?'.ModelExporter::class,
				'ModelImporter' => '?'.ModelImporter::class,
			]
		);
	}

	protected function trans( ?string $id, array $parameters = [], string $domain = null, string $locale = null ): string {
		return $this->container->get('translator')->trans( $id, $parameters, $domain ?? $this->defaultDomain, $locale );
	}

	public function __construct( EntityManagerInterface $entityManager )
	{
		self::$_baseEntityManager = $entityManager;
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

	public function json( mixed $data, int $status = 200, array $headers = [], array $context = [] ): JsonResponse
	{
		return parent::json( $this->container->get('ModelNormalizer')->normalize( $data ), $status, $headers, $context );
	}
}
