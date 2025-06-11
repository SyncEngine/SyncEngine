<?php

namespace SyncEngine\Structure\Data\Interface;

interface RecursiveDataInterface
{
	public function get( $key = null );
	public function set( $value, $key = null );
	public function unset( $key );

	public function getByKey( $key );
	public function setByKey( $value, $key );
	public function unsetByKey( $key );
}
