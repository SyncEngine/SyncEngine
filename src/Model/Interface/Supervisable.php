<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Model\Abstract\AbstractModel;

interface Supervisable
{
	public function _getConfig( $key = null, $default = null );
	public function getSupervisor();
	public function supportsSupervisor( string|AbstractModel $type );
}
