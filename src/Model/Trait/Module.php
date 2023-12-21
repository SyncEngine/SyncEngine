<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Model\ModuleModel;
use SyncEngine\Service\Modules;

trait Module
{
	private ModuleModel $module;

	public function isFromModule(): bool
	{
		return ! empty( $this->getModule() );
	}

	public function getModule(): ModuleModel|null
	{
		if ( ! empty( $this->module ) ) {
			return $this->module;
		}

		return $this->module ?? null;
	}

	/**
	 * @throws \Exception
	 */
	public function setModule( ModuleModel $module ): void
	{
		$this->module = $module;
	}
}
