<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Annotation\Ignore;
use SyncEngine\Model\Interface\Installable;
use SyncEngine\Service\Provider\Modules;

abstract class ModuleModel implements Installable
{
	const AUTHOR      = '';
	const VERSION     = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Human-readable description used in the interface.
	 *
	 * @var string
	 */
	public string $description = '';

	public function __construct()
	{
		// Construct.
	}

	public function install(): bool
	{
		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function update(): bool
	{
		return true;
	}

	public function handleRequest( Request $request ): ?Response
	{
		return null;
	}

	public function renderRequest( Request $request ): Response|array|null
	{
		return null;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getAuthor(): string
	{
		return $this::AUTHOR;
	}

	public function getVersion(): string
	{
		return $this::VERSION;
	}

	final static function isModule( $class ): bool
	{
		return $class instanceof ModuleModel;
	}

	#[Ignore]
	final public function getClassLocator(): string
	{
		return static::_getClassLocator();
	}

	final public static function _getClassLocator(): string
	{
		return Modules::getModuleName( static::class );
	}
}
