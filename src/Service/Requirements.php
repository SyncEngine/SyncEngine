<?php

namespace SyncEngine\Service;

/**
 * Reads requirements.json and exposes normalized PHP requirement checks.
 *
 * This service is intentionally independent from the CoreUpdater module so the
 * System Info page keeps working when that module is not installed.
 */
class Requirements
{
	private const EXTENSION_LABELS = [
		'curl'       => 'cURL',
		'gd'         => 'GD',
		'mbstring'   => 'Multibyte String',
		'mysql'      => 'MySQL',
		'mysqli'     => 'MySQLi',
		'pdo_mysql'  => 'PDO MySQL',
		'pdo_pgsql'  => 'PDO PostgreSQL',
		'pdo_sqlite' => 'PDO SQLite',
		'simplexml'  => 'SimpleXML',
		'sqlite'     => 'SQLite',
		'sqlite3'    => 'SQLite3',
	];

	private ?array $requirements = null;

	public function __construct(
		private readonly string $projectDir,
	) {}

	/**
	 * Load and decode requirements.json once.
	 */
	public function load(): array
	{
		if ( null !== $this->requirements ) {
			return $this->requirements;
		}

		$path = $this->projectDir . DIRECTORY_SEPARATOR . 'requirements.json';
		if ( ! is_file( $path ) ) {
			return $this->requirements = [];
		}

		$content = file_get_contents( $path );
		if ( false === $content ) {
			return $this->requirements = [];
		}

		$data = json_decode( $content, true );

		return $this->requirements = is_array( $data ) ? $data : [];
	}

	/**
	 * Get the PHP version requirement and its status.
	 *
	 * @return array{current: string, minimum: mixed, recommended: mixed, maximum: mixed, status: string, help: mixed}
	 */
	public function getPhpVersion( string $current = PHP_VERSION ): array
	{
		$config = $this->load()['php']['version'] ?? [];

		if ( ! is_array( $config ) ) {
			$config = [ 'minimum' => $config ];
		}

		$minimum     = $config['minimum'] ?? null;
		$recommended = $config['recommended'] ?? null;
		$maximum     = $config['maximum'] ?? null;

		$status = 'ok';
		if ( $minimum && version_compare( $current, (string) $minimum, '<' ) ) {
			$status = 'error';
		} elseif ( $recommended && version_compare( $current, (string) $recommended, '<' ) ) {
			$status = 'warning';
		} elseif ( $maximum && version_compare( $current, (string) $maximum, '>' ) ) {
			$status = 'warning';
		}

		return [
			'current'     => $current,
			'minimum'     => $minimum,
			'recommended' => $recommended,
			'maximum'     => $maximum,
			'status'      => $status,
			'help'        => $config['help'] ?? null,
		];
	}

	/**
	 * Get every configured PHP extension with its load status.
	 *
	 * @return list<array{name: string, label: string, required: bool, recommended: bool, alternatives: array, loaded: bool, loadedVia: ?string, status: string, note: mixed, help: mixed}>
	 */
	public function getExtensions(): array
	{
		$extensions = $this->load()['php']['extensions'] ?? [];
		if ( ! is_array( $extensions ) ) {
			return [];
		}

		$result = [];
		foreach ( $extensions as $extension ) {
			if ( is_string( $extension ) ) {
				$extension = [ 'name' => $extension, 'required' => true ];
			}
			if ( ! is_array( $extension ) ) {
				continue;
			}

			$name = (string) ( $extension['name'] ?? '' );
			if ( '' === $name ) {
				continue;
			}

			$required     = (bool) ( $extension['required'] ?? true );
			$recommended  = (bool) ( $extension['recommended'] ?? false );
			$alternatives = is_array( $extension['alternatives'] ?? null ) ? $extension['alternatives'] : [];

			$loadedVia = null;
			if ( extension_loaded( $name ) ) {
				$loadedVia = $name;
			} else {
				foreach ( $alternatives as $alternative ) {
					if ( extension_loaded( (string) $alternative ) ) {
						$loadedVia = (string) $alternative;
						break;
					}
				}
			}

			$loaded = null !== $loadedVia;
			if ( $loaded ) {
				$status = 'ok';
			} elseif ( $required ) {
				$status = 'error';
			} elseif ( $recommended ) {
				$status = 'warning';
			} else {
				$status = 'info';
			}

			$result[] = [
				'name'         => $name,
				'label'        => $this->formatExtensionLabel( $name ),
				'required'     => $required,
				'recommended'  => $recommended,
				'alternatives' => $alternatives,
				'loaded'       => $loaded,
				'loadedVia'    => $loadedVia,
				'status'       => $status,
				'note'         => $extension['note'] ?? null,
				'help'         => $extension['help'] ?? null,
			];
		}

		return $result;
	}

	/**
	 * Get every configured PHP ini setting with its current value and status.
	 *
	 * @param array<string, string>|null $currentValues Optional current values for testing.
	 *
	 * @return list<array{key: string, current: string, minimum: mixed, recommended: mixed, maximum: mixed, status: string, help: mixed}>
	 */
	public function getIniSettings( ?array $currentValues = null ): array
	{
		$settings = $this->load()['php']['ini'] ?? [];
		if ( ! is_array( $settings ) ) {
			return [];
		}

		$result = [];
		foreach ( $settings as $key => $config ) {
			$current = $currentValues[ $key ] ?? ini_get( $key );
			if ( false === $current ) {
				$current = 'Off';
			}

			$minimum     = null;
			$recommended = null;
			$maximum     = null;
			$help        = null;

			if ( is_array( $config ) ) {
				$minimum     = $config['minimum'] ?? null;
				$recommended = $config['recommended'] ?? null;
				$maximum     = $config['maximum'] ?? null;
				$help        = $config['help'] ?? null;
			} else {
				$minimum = $config;
			}

			$result[] = [
				'key'         => (string) $key,
				'current'     => (string) $current,
				'minimum'     => $minimum,
				'recommended' => $recommended,
				'maximum'     => $maximum,
				'status'      => $this->getIniStatus( (string) $key, (string) $current, $minimum, $recommended, $maximum ),
				'help'        => $help,
			];
		}

		return $result;
	}

	private function getIniStatus( string $key, string $current, mixed $minimum, mixed $recommended, mixed $maximum ): string
	{
		if ( 'memory_limit' === $key ) {
			$currentBytes     = $this->parseMemoryLimit( $current );
			$minimumBytes     = null !== $minimum ? $this->parseMemoryLimit( (string) $minimum ) : null;
			$recommendedBytes = null !== $recommended ? $this->parseMemoryLimit( (string) $recommended ) : null;

			if ( null !== $minimumBytes && $currentBytes > 0 && $currentBytes < $minimumBytes ) {
				return 'error';
			}
			if ( null !== $recommendedBytes && $currentBytes > 0 && $currentBytes < $recommendedBytes ) {
				return 'warning';
			}

			return 'ok';
		}

		if ( 'max_execution_time' === $key ) {
			$currentInt     = (int) $current;
			$minimumInt     = null !== $minimum ? (int) $minimum : null;
			$recommendedInt = null !== $recommended ? (int) $recommended : null;
			$maximumInt     = null !== $maximum ? (int) $maximum : null;

			if ( null !== $minimumInt && $currentInt > 0 && $currentInt < $minimumInt ) {
				return 'error';
			}
			if ( null !== $recommendedInt && $currentInt > 0 && $currentInt < $recommendedInt ) {
				return 'warning';
			}
			if ( null !== $maximumInt && $currentInt > $maximumInt ) {
				return 'warning';
			}

			return 'ok';
		}

		if ( is_bool( $minimum ) ) {
			return filter_var( $current, FILTER_VALIDATE_BOOLEAN ) === $minimum ? 'ok' : 'warning';
		}
		if ( is_numeric( $minimum ) && (float) $current < (float) $minimum ) {
			return 'warning';
		}
		if ( is_numeric( $maximum ) && (float) $current > (float) $maximum ) {
			return 'warning';
		}

		return 'ok';
	}

	/**
	 * Parse a PHP memory_limit value to bytes. A value of -1 means unlimited.
	 */
	private function parseMemoryLimit( string $value ): int
	{
		if ( '' === $value || '-1' === $value ) {
			return -1;
		}

		$value    = trim( $value );
		$number   = (int) $value;
		$lastChar = strtolower( substr( $value, -1 ) );

		switch ( $lastChar ) {
			case 'g':
				$number *= 1024;
			// no break
			case 'm':
				$number *= 1024;
			// no break
			case 'k':
				$number *= 1024;
		}

		return $number;
	}

	private function formatExtensionLabel( string $name ): string
	{
		return self::EXTENSION_LABELS[ $name ] ?? ucwords( str_replace( '_', ' ', $name ) );
	}
}
