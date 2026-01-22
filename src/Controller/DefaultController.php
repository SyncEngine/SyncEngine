<?php

namespace SyncEngine\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Service\ModelExporter;
use SyncEngine\Service\ModelImporter;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Service\Locator\Blueprints;
use SyncEngine\Service\Locator\Codecs;
use SyncEngine\Service\Locator\Columns;
use SyncEngine\Service\Locator\Tasks;
use SyncEngine\Service\Locator\Webservices;

class DefaultController extends AbstractController
{
	private static ContainerInterface $_container;

	protected string $defaultDomain = 'messages';

	public static function getContainer(): ?ContainerInterface
	{
		return self::$_container ?? null;
	}

	public function getParameter( string $name, false|string $prefix = 'syncengine' ): array|bool|float|int|null|string|\UnitEnum
	{
		if ( $prefix ) {
			$name = $prefix . '.' . $name;
		}
		return parent::getParameter( $name );
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
				Columns::class => '?'.Columns::class,
				Codecs::class => '?'.Codecs::class,
				Tasks::class => '?'.Tasks::class,
				Webservices::class => '?'.Webservices::class,
				Blueprints::class => '?'.Blueprints::class,
				ModelNormalizer::class => '?'.ModelNormalizer::class,
				ModelExporter::class => '?'.ModelExporter::class,
				ModelImporter::class => '?'.ModelImporter::class,
			]
		);
	}

	protected function trans( ?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null ): string {
		return $this->container->get('translator')->trans( $id, $parameters, $domain ?? $this->defaultDomain, $locale );
	}

	#[Required]
	public function setContainer( ContainerInterface $container ): ?ContainerInterface
	{
		$previous = parent::setContainer( $container );

		if ( ! isset( self::$_container ) ) {
			self::$_container = $this->container;
		}

		return $previous;
	}

	/**
	 * @return EntityManagerInterface|null
	 */
	public static function getEntityManager(): ?EntityManagerInterface
	{
		return self::get( 'entitymanager' );
	}

	public function json( mixed $data, int $status = 200, array $headers = [], array $context = [] ): JsonResponse
	{
		return parent::json( $this->container->get( ModelNormalizer::class )->normalize( $data ), $status, $headers, $context );
	}
}
