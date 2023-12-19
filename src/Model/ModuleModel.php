<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class ModuleModel
{
	const name = '';
	const description = '';
	const author = '';
	const version = '';

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
		return $this::name;
	}

	public function getDescription(): string
	{
		return $this::description;
	}

	public function getAuthor(): string
	{
		return $this::author;
	}

	public function getVersion(): string
	{
		return $this::version;
	}

	final static function isModule( $class ): bool
	{
		return $class instanceof ModuleModel;
	}

	final public function getClassLocator(): string
	{
		return static::_getClassLocator();
	}

	final public static function _getClassLocator(): string
	{
		return ( new \ReflectionClass( static::class ) )->getShortName();
	}
}
