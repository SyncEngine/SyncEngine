<?php

namespace SyncEngine\Model\Interface;

interface Supervisable
{
	public function getSupervisor();
	public function supportsSupervisor( string $type );
}
