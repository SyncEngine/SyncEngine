<?php

namespace App\Repository\Interface;

interface Searchable
{
	public function searchBy( array $search, array $criteria, array $orderBy = null, $limit = null, $offset = null );

	public function searchOneBy( array $search, array $criteria, array $orderBy = null, $offset = null );
}
