<?php

namespace SyncEngine\Framework;

use Composer\Semver\Semver;
use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Kernel;

class ModuleRegistryManager
{
	public const MODULE_STATUS_FILE = 'modules/registry.json';
	public const MODULE_REGISTER_FILE = 'modules/registry.php';
	public const MODULE_SERVICES_FILE = 'config/modules.yaml';

	private const SERVICE_TEMPLATE = '
    SyncEngine\Module\%vendor%\%module%\:
        resource: "%kernel.project_dir%/modules/%vendor%/%module%/src"
        lazy: false';

	public function __construct(
		private readonly string $projectDir,
		private readonly string $coreVersion = Kernel::VERSION,
	) {}

	public function refreshRegistry(): array
	{
		$registry = $this->buildModuleRegistry();

		$this->writeRegistryFile( $registry['register'] );
		$this->writeServicesFile( $registry['services'] );
		$this->writeModuleStatusFile( $registry['status'] );

		return $registry;
	}

	public function buildModuleRegistry( ?string $serviceTemplate = null ): array
	{
		$serviceTemplate = $serviceTemplate ?? self::SERVICE_TEMPLATE;
		$modulesPath = $this->projectDir . DIRECTORY_SEPARATOR . 'modules';
		$vendors     = $this->getDirectories( $modulesPath );
		$register    = [];
		$services    = [];
		$status      = [
			'updated_at'   => date( DATE_ATOM ),
			'core_version' => $this->normalizeVersion( $this->coreVersion ),
			'manual'       => [
				'disabled' => $this->getDisabledModules(),
			],
			'active'       => [],
			'disabled'     => [],
		];

		foreach ( $vendors as $vendor ) {
			$vendorPath = $modulesPath . DIRECTORY_SEPARATOR . $vendor;
			$modules    = $this->getDirectories( $vendorPath );

			foreach ( $modules as $module ) {
				$package = $vendor . '/' . $module;
				$reason  = $this->getModuleSkipReason( $vendor, $module );

				if ( $reason ) {
					$status['disabled'][ $package ] = [
						'package'    => $package,
						'vendor'     => $vendor,
						'module'     => $module,
						'reason'     => $reason,
						'constraint' => $this->getModuleCoreConstraint( $vendor, $module ),
					];

					continue;
				}

				$autoloadFilePath = $vendorPath . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'register.php';
				if ( file_exists( $autoloadFilePath ) ) {
					$register[] = "include __DIR__ . '/" . $vendor . '/' . $module . "/register.php';";
				}

				if ( is_dir( $vendorPath . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'src' ) ) {
					$services[] = str_replace(
						[ '%vendor%', '%module%' ],
						[ $vendor, $module ],
						$serviceTemplate
					);
				}

				$status['active'][ $package ] = [
					'package'    => $package,
					'vendor'     => $vendor,
					'module'     => $module,
					'constraint' => $this->getModuleCoreConstraint( $vendor, $module ),
				];
			}
		}

		return [
			'register' => $register,
			'services' => $services,
			'status'   => $status,
		];
	}

	public function getDisabledModulesFromStatus(): array
	{
		$status = $this->getModuleStatus();
		if ( ! $status ) {
			return [];
		}

		$disabled = $status['disabled'] ?? [];

		return is_array( $disabled ) ? array_values( $disabled ) : [];
	}

	public function getModuleStatus(): array
	{
		$file = $this->getModuleStatusFilePath();
		if ( ! is_file( $file ) ) {
			return [];
		}

		$contents = file_get_contents( $file );
		if ( false === $contents ) {
			return [];
		}

		$status = json_decode( $contents, true );

		return is_array( $status ) ? $status : [];
	}

	public function extractModulePackagesFromOutput( string $output ): array
	{
		if ( '' === trim( $output ) ) {
			return [];
		}

		$packages = [];

		// Detect module package from filesystem paths such as modules/Vendor/Module/...
		preg_match_all( '#modules/([^/\s]+)/([^/\s]+)/#', $output, $pathMatches, PREG_SET_ORDER );
		foreach ( $pathMatches as $match ) {
			$packages[] = $match[1] . '/' . $match[2];
		}

		// Detect module package from class namespaces such as
		// SyncEngine\Module\Vendor\Module\SomeClass or SyncEngine\\Module\\Vendor\\Module...
		preg_match_all(
			'#SyncEngine(?:\\\\)+Module(?:\\\\)+([A-Za-z0-9_]+)(?:\\\\)+([A-Za-z0-9_]+)#',
			$output,
			$namespaceMatches,
			PREG_SET_ORDER
		);
		foreach ( $namespaceMatches as $match ) {
			$packages[] = $match[1] . '/' . $match[2];
		}

		return array_values( array_unique( $packages ) );
	}

	public function extractModulePackagesFromThrowable( \Throwable $throwable ): array
	{
		$segments = [
			$throwable->getMessage(),
			$throwable->getTraceAsString(),
		];

		$file = $throwable->getFile();
		if ( is_string( $file ) && $file ) {
			$segments[] = $file;
		}

		$payload = implode( "\n", array_filter( $segments ) );

		return $this->extractModulePackagesFromOutput( $payload );
	}

	public function disableModule( string $package, string $reason = 'Disabled manually' ): void
	{
		$this->appendDisabledModules( [ $package ], $reason );
		$this->refreshRegistry();
	}

	public function enableModule( string $package ): void
	{
		$this->removeDisabledModule( $package );
		$this->refreshRegistry();
	}

	public function appendDisabledModules( array $packages, string $reason ): void
	{
		$list = $this->getDisabledModules();
		foreach ( $packages as $package ) {
			$list[ $package ] = $reason;
		}

		$this->writeDisabledModules( $list );
	}

	public function removeDisabledModule( string $package ): void
	{
		$list = $this->getDisabledModules();
		if ( ! isset( $list[ $package ] ) ) {
			return;
		}

		unset( $list[ $package ] );
		$this->writeDisabledModules( $list );
	}

	private function getDirectories( string $path ): array
	{
		if ( ! is_dir( $path ) ) {
			return [];
		}

		$directories = scandir( $path );
		if ( false === $directories ) {
			throw new \RuntimeException( sprintf( 'Permission denied: unable to scan directory "%s".', $path ) );
		}

		return array_values(
			array_filter(
				$directories,
				static function ( string $folder ) use ( $path ): bool {
					return is_dir( $path . DIRECTORY_SEPARATOR . $folder ) && ! in_array( $folder, [ '.', '..' ], true );
				}
			)
		);
	}

	private function getModuleSkipReason( string $vendor, string $module ): ?string
	{
		$package  = $vendor . '/' . $module;
		$constraint = $this->getModuleCoreConstraint( $vendor, $module );
		$disabled = $this->getDisabledModules();

		if ( isset( $disabled[ $package ] ) ) {
			$reason = trim( (string) $disabled[ $package ] );

			// Ignore stale compatibility disables if module no longer declares a core constraint.
			if ( ! $constraint && $this->isCompatibilityDisableReason( $reason ) ) {
				return null;
			}

			return $reason ?: 'Disabled manually';
		}

		if ( ! $constraint ) {
			return null;
		}

		$coreVersion = $this->normalizeVersion( $this->coreVersion );
		if ( $this->matchesConstraintExpression( $coreVersion, $constraint ) ) {
			return null;
		}

		return sprintf( 'Module requires core version "%s" (current: %s).', $constraint, $coreVersion );
	}

	private function isCompatibilityDisableReason( string $reason ): bool
	{
		$reason = strtolower( trim( $reason ) );

		return str_starts_with( $reason, 'module requires core version' );
	}

	private function getDisabledModules(): array
	{
		$status = $this->getModuleStatus();
		if ( ! $status ) {
			return [];
		}

		return $status['manual']['disabled'] ?? [];
	}

	private function getModuleStatusFilePath(): string
	{
		return $this->projectDir . DIRECTORY_SEPARATOR . self::MODULE_STATUS_FILE;
	}

	private function getModuleCoreConstraint( string $vendor, string $module ): ?string
	{
		$file = $this->projectDir
		        . DIRECTORY_SEPARATOR
		        . 'modules'
		        . DIRECTORY_SEPARATOR
		        . $vendor
		        . DIRECTORY_SEPARATOR
		        . $module
		        . DIRECTORY_SEPARATOR
		        . 'module.json';

		if ( ! is_file( $file ) ) {
			return null;
		}

		$manifest = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $manifest ) ) {
			return null;
		}

		$constraint = $manifest['require']['syncengine'] ?? null;

		if ( ! is_string( $constraint ) ) {
			return null;
		}

		$constraint = trim( $constraint );

		return $constraint ?: null;
	}

	private function writeDisabledModules( array $disabled ): void
	{
		$file = $this->projectDir . DIRECTORY_SEPARATOR . self::MODULE_STATUS_FILE;
		$dir  = dirname( $file );

		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0775, true );
		}

		// Read current registry
		$status = $this->getModuleStatus();
		if ( ! is_array( $status ) ) {
			$status = [];
		}

		// Ensure structure exists
		if ( ! isset( $status['manual'] ) || ! is_array( $status['manual'] ) ) {
			$status['manual'] = [];
		}

		// Update manual disabled
		$status['manual']['disabled'] = $disabled;
		$status['updated_at']          = date( DATE_ATOM );

		$this->writeModuleStatusFile( $status );
	}

	private function writeRegistryFile( array $register ): void
	{
		$content = "<?php\n" . implode( "\n", $register );

		$this->safeFilePutContents(
			$this->projectDir . DIRECTORY_SEPARATOR . self::MODULE_REGISTER_FILE,
			$content
		);
	}

	private function writeServicesFile( array $services ): void
	{
		$content = $this->formatServicesContent( $services );

		$this->safeFilePutContents(
			$this->projectDir . DIRECTORY_SEPARATOR . self::MODULE_SERVICES_FILE,
			$content
		);
	}

	private function writeModuleStatusFile( array $status ): void
	{
		$content = $this->formatStatusContent( $status );

		$this->safeFilePutContents(
			$this->projectDir . DIRECTORY_SEPARATOR . self::MODULE_STATUS_FILE,
			$content
		);
	}

	private function formatServicesContent( array $services ): string
	{
		$content = '
services:
' . implode( "\n", $services ) . '

    _defaults:
        autowire: true
        autoconfigure: true

    # Autoconfigure service tags based on instances.
    _instanceof:
        SyncEngine\Model\Abstract\EntityModel:
            tags: [ \'syncengine.model.entity\' ]
        SyncEngine\Model\ModuleModel:
            tags: [ \'syncengine.model.module\' ]
        SyncEngine\Model\BlueprintModel:
            tags: [ \'syncengine.model.blueprint\' ]
        SyncEngine\Model\ColumnModel:
            tags: [ \'syncengine.model.column\' ]
        SyncEngine\Model\CodecModel:
            tags: [ \'syncengine.model.codec\' ]
        SyncEngine\Model\TaskModel:
            tags: [ \'syncengine.model.task\' ]
        SyncEngine\Model\WebserviceModel:
            tags: [ \'syncengine.model.webservice\' ]';

		return $content;
	}

	private function formatStatusContent( array $status ): string
	{
		$content = json_encode( $status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $content ) {
			throw new \RuntimeException( 'Failed to encode module status registry to JSON.' );
		}

		return $content . "\n";
	}

	private function safeFilePutContents( string $filePath, string $content ): void
	{
		( new Filesystem() )->dumpFile( $filePath, $content );

		clearstatcache( true, $filePath );
		if ( function_exists( 'opcache_invalidate' ) ) {
			@opcache_invalidate( $filePath, true );
		}
	}

	private function matchesConstraintExpression( string $version, string $expression ): bool
	{
		$version = $this->normalizeVersion( $version );
		$expression = trim( $expression );

		if ( '' === $expression || '*' === $expression ) {
			return true;
		}

		if ( Semver::satisfies( $version, $expression ) ) {
			return true;
		}

		return $this->matchesPrereleaseGteTolerance( $version, $expression );
	}

	private function matchesPrereleaseGteTolerance( string $version, string $expression ): bool
	{
		if ( ! $this->isPrerelease( $version ) ) {
			return false;
		}

		$versionBase = $this->normalizeVersionBase( $version );
		$orGroups    = preg_split( '/\s*\|\|\s*/', trim( $expression ) ) ?: [];

		foreach ( $orGroups as $group ) {
			$group = trim( $group );
			if ( '' === $group ) {
				continue;
			}

			$andParts = preg_split( '/[\s,]+/', $group ) ?: [];
			$matches  = true;

			foreach ( $andParts as $constraint ) {
				$constraint = trim( $constraint );
				if ( '' === $constraint || '*' === $constraint ) {
					continue;
				}

				if ( ! preg_match( '/^>=\s*(.+)$/', $constraint, $parts ) ) {
					$matches = false;
					break;
				}

				$target = $this->normalizeVersion( $parts[1] );
				if ( $versionBase !== $this->normalizeVersionBase( $target ) ) {
					$matches = false;
					break;
				}

				if ( version_compare( $version, $target, '<' ) ) {
					$matches = false;
					break;
				}
			}

			if ( $matches ) {
				return true;
			}
		}

		return false;
	}

	private function isPrerelease( string $version ): bool
	{
		return str_contains( $version, '-' );
	}

	private function normalizeVersionBase( string $version ): string
	{
		$version = $this->normalizeVersion( $version );
		$parts   = preg_split( '/[-+]/', $version ) ?: [];

		return (string) ( $parts[0] ?? $version );
	}

	private function normalizeVersion( string $version ): string
	{
		$version = trim( $version );
		if ( str_starts_with( strtolower( $version ), 'v' ) ) {
			$version = substr( $version, 1 );
		}

		return $version;
	}
}


