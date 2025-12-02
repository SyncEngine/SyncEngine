<?php

namespace SyncEngine\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 *
 * @method array|null find($id, $lockMode = null, $lockVersion = null)
 * @method array|null findOneBy(array $criteria, array $orderBy = null)
 * @method array      findAll()
 * @method array      findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessengerMessageRepository
{
	private EntityManagerInterface $em;
	private string $table;

	public function __construct(EntityManagerInterface $em, #[Autowire( '%env(string:DB_TABLE_PREFIX)%' )] $prefix ='')
	{
		$this->em = $em;
		$this->table = $prefix . 'messenger_messages';
	}

	public function find($id, $lockMode = null, $lockVersion = null): ?array
	{
		$conn = $this->em->getConnection();

		$sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue('id', $id);

		$row = $stmt->executeQuery()->fetchAssociative();

		return $row ?: null;
	}

	public function findOneBy(array $criteria, array $orderBy = null): ?array
	{
		$rows = $this->findBy($criteria, $orderBy, 1, 0);

		return $rows[0] ?? null;
	}

	public function findAll(): array
	{
		$conn = $this->em->getConnection();

		$sql = "SELECT * FROM {$this->table}";
		$stmt = $conn->executeQuery($sql);

		return $stmt->fetchAllAssociative();
	}

	public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
	{
		$conn = $this->em->getConnection();

		$where = [];
		$params = [];
		$types  = [];

		foreach ($criteria as $field => $value) {
			if (is_array($value)) {
				$where[] = "$field IN (:{$field})";
				$params[$field] = $value;
				$types[$field]  = ArrayParameterType::STRING;
			} else {
				$where[] = "$field = :{$field}";
				$params[$field] = $value;
			}
		}

		$sql = "SELECT * FROM {$this->table}";

		if ($where) {
			$sql .= " WHERE " . implode(' AND ', $where);
		}
		
		if ($orderBy) {
			$orderParts = [];
			foreach ($orderBy as $field => $direction) {
				$direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
				$orderParts[] = "{$field} {$direction}";
			}

			$sql .= " ORDER BY " . implode(', ', $orderParts);
		}

		if ($limit !== null) {
			$sql .= " LIMIT " . (int) $limit;
		}

		if ($offset !== null) {
			$sql .= " OFFSET " . (int) $offset;
		}

		$stmt = $conn->prepare($sql);

		foreach ($params as $key => $value) {
			if (isset($types[$key]) && $types[$key] === ArrayParameterType::STRING) {
				$stmt->bindValue($key, $value, ArrayParameterType::STRING);
			} else {
				$stmt->bindValue($key, $value);
			}
		}

		return $stmt->executeQuery()->fetchAllAssociative();
	}
}
