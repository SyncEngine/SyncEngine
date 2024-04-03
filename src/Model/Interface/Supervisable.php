<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Model\Abstract\AbstractModel;

interface Supervisable
{
	public function getSupervisor();
	public function supportsSupervisor( string|AbstractModel $type );
	public function runSupervisorValidate(): bool;
}
