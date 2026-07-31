<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Model\Abstract\AbstractModel;

interface Supervisable extends Configurable
{
	public function getSupervisor();
	public function setSupervisor( AbstractModel $supervisor ): self;
	public function supportsSupervisor( string|AbstractModel $type );
	public function runSupervisorValidate(): bool;
}
