<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SyncEngine\Form\Type;

use Symfony\Component\Form\DataTransformerInterface;
use SyncEngine\Form\Fields\Collection\FieldCollection;

/**
 * @implements DataTransformerInterface<mixed, mixed>
 */
class DatabaseDsnType extends DsnType
{
	public function dsnFields(): FieldCollection
	{
		$fields = parent::dsnFields();

		$fields['username']['conditions'] = [ 'protocol' => [ 'operator' => '!=', 'compare' => 'sqlite' ] ];
		$fields['password']['conditions'] = [ 'protocol' => [ 'operator' => '!=', 'compare' => 'sqlite' ] ];
		$fields['host']['conditions'] = [ 'protocol' => [ 'operator' => '!=', 'compare' => 'sqlite' ] ];
		$fields['port']['conditions'] = [ 'protocol' => [ 'operator' => '!=', 'compare' => 'sqlite' ] ];

		$fields['query']['choices'] = [
			'key' => [
				'charset'       => 'charset',
				'serverVersion' => 'serverVersion',
			],
		];

		return $fields;
	}

	public function getProtocols(): array
	{
		return [
			[ 'label' => 'SQLite', 'value' => 'sqlite' ],
			[ 'label' => 'MySQL', 'value' => 'mysql' ],
			[ 'label' => 'PostgreSQL', 'value' => 'pgsql' ],
			[ 'label' => 'MS SQL Server', 'value' => 'mssql' ],
			[ 'label' => 'Oracle', 'value' => 'oci8' ],
		];
	}

	public function dsnDefaults(): array
	{
		return  [
			'port' => [
				'sqlite'  => null,
				'mysql'   => 3306,
				'pgsql'   => 5432,
				'mssql'   => 1433,
				'oci8'    => 1521,
			],
			'query' => [
				'charset' => 'utf8',
			],
		];
	}
}
