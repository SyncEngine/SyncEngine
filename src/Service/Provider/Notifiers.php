<?php

namespace SyncEngine\Service\Provider;

use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Model\ModuleModel;
use SyncEngine\Model\NotifierModel;

class Notifiers implements ProviderInterface
{
	public function __construct(
		private readonly ServiceLocator $container,
		private readonly Modules $modulesService,
	) {}

	public function get( $name ): ?NotifierModel
	{
		try {
			$notifier = $this->container->get( $name ) ?? null;

			if ( $notifier instanceof NotifierModel ) {
				if ( str_contains( $name, ':' ) ) {
					$parts  = explode( ':', $name );
					$module = $this->modulesService->get( $parts[0] );

					$notifier->setModule( $module );
				}
				return $notifier;
			}
		} catch ( \Throwable $throwable ) {
			// Nope.
		}

		// @todo Error or log.
		return null;
	}

	/**
	 * @return NotifierModel[]
	 */
	public function getAll(): array
	{
		static $notifiers = [];
		if ( $notifiers ) {
			return $notifiers;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$notifier = $this->get( $tag );
			if ( $notifier ) {
				$notifiers[ $notifier->getClassLocator() ] = $notifier;
			}
		}

		return $notifiers;
	}

	/**
	 * @return NotifierModel[]
	 */
	public function getAllFromModule( ModuleModel|string $module ): array
	{
		$notifiers = [];

		$moduleName = $module instanceof ModuleModel ? $module->getClassLocator() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$notifier = $this->get( $tag );
				if ( $notifier ) {
					$notifiers[ $notifier->getClassLocator() ] = $notifier;
				}
			}
		}

		return $notifiers;
	}

	/**
	 * @return array
	 */
	public function getTypes(): array
	{
		return array_keys( $this->getAll() );
	}

	/**
	 * @return array[]
	 */
	public function getNormalized(): array
	{
		$notifiers = [];
		foreach ( $this->getAll() as $key => $notifier ) {
			$notifiers[ $key ] = $notifier->normalize();
		}

		return $notifiers;
	}
}
