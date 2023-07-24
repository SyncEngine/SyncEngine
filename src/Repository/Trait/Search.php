<?php

namespace App\Repository\Trait;

use Doctrine\ORM\QueryBuilder;

trait Search
{
	private function createSearchQueryBuilder( array $search, array $criteria = null, array $orderBy = null, $limit = null, $offset = null ): QueryBuilder
	{
		$queryBuilder = $this->createQueryBuilder( 'd' );

		foreach ( $search as $key => $value ) {
			$queryBuilder->andWhere( 'd.' . $key . ' LIKE :search_' . $key );
			$queryBuilder->setParameter( 'search_' . $key, '%' . $value . '%' );
		}

		if ( $criteria ) {
			foreach ( $criteria as $key => $value ) {
				if ( is_array( $value ) ) {
					$queryBuilder->andWhere( 'd.' . $key . ' IN (:val_' . $key . ')' );
				} else {
					$queryBuilder->andWhere( 'd.' . $key . ' = :val_' . $key );
				}
				$queryBuilder->setParameter( 'val_' . $key, $value );
			}
		}

		if ( $orderBy ) {
			foreach ( $orderBy as $sort => $order ) {
				$queryBuilder->orderBy( $sort, $order );
			}
		}

		if ( $limit ) {
			$queryBuilder->setMaxResults( $limit );
		}

		if ( $offset ) {
			$queryBuilder->setFirstResult( $offset );
		}

		return $queryBuilder;
	}

	public function searchBy( array $search, array $criteria = null, array $orderBy = null, $limit = null, $offset = null ): array
	{
		return $this->createSearchQueryBuilder( $search, $criteria, $orderBy, $limit, $offset )->getQuery()->getResult();
	}

	public function searchOneBy( array $search, array $criteria = null, array $orderBy = null, $offset = null ): mixed
	{
		return $this->createSearchQueryBuilder( $search, $criteria, $orderBy, 1, $offset )->getQuery()->getOneOrNullResult();
	}
}
