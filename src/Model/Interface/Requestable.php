<?php

namespace App\Model\Interface;

interface Requestable
{
	public function retrieve( array $config );

	public function send( array $config, $data );
}
